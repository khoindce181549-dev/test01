<?php
namespace Quizably\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * What the popup and slide-in shortcodes share: *when* they open, and the one small script that
 * opens, closes and focuses them.
 *
 *   trigger="click"   (default) only when the visitor clicks the button, exactly as before
 *   trigger="delay"   opens by itself after delay="<seconds>" (default 5, 0-600)
 *   trigger="scroll"  opens once the visitor has scrolled scroll="<percent>" down the page
 *                     (default 50, 1-100)
 *   trigger="exit"    opens when the pointer leaves through the top of the window (exit intent).
 *                     Only on devices with a fine, hovering pointer (a mouse): touch devices
 *                     have no pointer to leave with, so there it never fires and the button stays
 *                     the way in.
 *   once="session|day|always"  how often an *automatic* open may repeat: once per browser
 *                     session (sessionStorage), once per 24 hours (localStorage), or every page
 *                     load. Default "session". Clicking the button always works whatever the cap.
 *
 * Attributes are validated here, in PHP, to a closed set of values; anything unrecognised falls
 * back to the default, so a typo can never break the page or reach the markup unescaped.
 *
 * Free, not Pro: these embeds already ship in the free plugin (see Pro\Gate) and the triggers are
 * plain client-side behaviour of that same code.
 */
final class EmbedTrigger
{
    public const TRIGGERS = [ 'click', 'delay', 'scroll', 'exit' ];
    public const ONCE     = [ 'session', 'day', 'always' ];

    public const DEFAULT_DELAY  = 5;
    public const MAX_DELAY      = 600;
    public const DEFAULT_SCROLL = 50;
    public const DEFAULT_ONCE   = 'session';

    /** Handle of the shared script (inline-only, like each embed's own CSS). */
    public const HANDLE = 'quizably-embed-triggers';

    /** @var array<string,int> Per-prefix counters so every embed on a page gets its own element id. */
    private static array $ids = [];

    /**
     * Shortcode attribute defaults for the trigger options. Empty means "not given".
     *
     * @return array<string,string>
     */
    public static function default_atts(): array
    {
        return [ 'trigger' => '', 'delay' => '', 'scroll' => '', 'once' => '' ];
    }

    /**
     * @param array<string,mixed> $atts Raw shortcode attributes.
     * @return array{trigger:string,delay:int,scroll:int,once:string}
     */
    public static function parse( array $atts ): array
    {
        $trigger = strtolower( trim( (string) ( $atts['trigger'] ?? '' ) ) );
        if ( ! in_array( $trigger, self::TRIGGERS, true ) ) {
            $trigger = 'click';
        }

        $once = strtolower( trim( (string) ( $atts['once'] ?? '' ) ) );
        if ( ! in_array( $once, self::ONCE, true ) ) {
            $once = self::DEFAULT_ONCE;
        }

        return [
            'trigger' => $trigger,
            'delay'   => self::bounded( $atts['delay'] ?? '', '/^\s*(\d+(?:\.\d+)?)\s*(?:s|sec|secs|seconds)?\s*$/i', 0, self::MAX_DELAY, self::DEFAULT_DELAY ),
            'scroll'  => self::bounded( $atts['scroll'] ?? '', '/^\s*(\d+(?:\.\d+)?)\s*%?\s*$/', 1, 100, self::DEFAULT_SCROLL ),
            'once'    => $once,
        ];
    }

    /**
     * The data-* attributes the script reads, ready to sit inside an opening tag. Empty for the
     * default click trigger, so an embed that never asked for a trigger renders the markup it
     * always did.
     *
     * @param array{trigger:string,delay:int,scroll:int,once:string} $cfg Result of parse().
     */
    public static function data_attributes( array $cfg ): string
    {
        if ( 'click' === $cfg['trigger'] ) {
            return '';
        }

        $out = ' data-trigger="' . esc_attr( $cfg['trigger'] ) . '"';
        if ( 'delay' === $cfg['trigger'] ) {
            $out .= ' data-delay="' . (int) $cfg['delay'] . '"';
        } elseif ( 'scroll' === $cfg['trigger'] ) {
            $out .= ' data-scroll="' . (int) $cfg['scroll'] . '"';
        }
        return $out . ' data-once="' . esc_attr( $cfg['once'] ) . '"';
    }

    /** A page-unique element id such as "quizably-popup-2". */
    public static function next_id( string $prefix ): string
    {
        self::$ids[ $prefix ] = ( self::$ids[ $prefix ] ?? 0 ) + 1;
        return sprintf( 'quizably-%s-%d', $prefix, self::$ids[ $prefix ] );
    }

    /** Forget issued ids (tests that render several pages in one process). */
    public static function reset(): void
    {
        self::$ids = [];
    }

    /**
     * Register and enqueue the shared script once per request, however many embed shortcode
     * classes ask. Carried entirely as inline data in the footer (no physical file), for the same
     * reason as each embed's own CSS - see QuizPopupShortcode::HANDLE.
     */
    public static function register_script(): void
    {
        if ( wp_script_is( self::HANDLE, 'registered' ) ) {
            return;
        }
        wp_register_script( self::HANDLE, false, [], QUIZABLY_VERSION, true );
        wp_enqueue_script( self::HANDLE );
        wp_add_inline_script( self::HANDLE, self::script() );
    }

    /**
     * Number in range, from a value that must match $pattern (capture 1 is the number). Anything
     * else - empty, text, negative - gives the default; a number outside the range is clamped.
     */
    private static function bounded( $value, string $pattern, int $min, int $max, int $default ): int
    {
        if ( 1 !== preg_match( $pattern, (string) $value, $m ) ) {
            return $default;
        }
        return max( $min, min( $max, (int) round( (float) $m[1] ) ) );
    }

    /**
     * Raw JS only - handed to wp_add_inline_script(). Finds every embed carrying
     * data-quizably-embed="popup|slidein" and wires up: the button, the close button, backdrop
     * click (popup), Escape, focus in and back out, a Tab trap while a popup is modal, and the
     * automatic triggers with their once-per-session/day cap. All storage access is try/catch:
     * private browsing and blocked-storage settings throw, and then the cap simply doesn't apply.
     */
    public static function script(): string
    {
        return <<<'JS'
(function(){
  var KINDS={
    popup:{trigger:'.quizably-popup-trigger',surface:'.quizably-popup-overlay',dialog:'.quizably-popup-inner',close:'.quizably-popup-close',modal:true},
    slidein:{trigger:'.quizably-slidein-trigger',surface:'.quizably-slidein-panel',dialog:'.quizably-slidein-panel',close:'.quizably-slidein-close',modal:false}
  };
  var FOCUSABLE='a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
  function store(once){try{return once==='day'?window.localStorage:window.sessionStorage;}catch(e){return null;}}
  function capped(key,once){
    if(once==='always')return false;
    try{
      var s=store(once);var v=s&&s.getItem(key);
      if(!v)return false;
      return once==='day'?(Date.now()-parseInt(v,10))<86400000:true;
    }catch(e){return false;}
  }
  function remember(key,once){
    if(once==='always')return;
    try{var s=store(once);if(s)s.setItem(key,String(Date.now()));}catch(e){}
  }
  function init(el){
    var kind=KINDS[el.getAttribute('data-quizably-embed')];
    if(!kind)return;
    var trigger=el.querySelector(kind.trigger);
    var surface=el.querySelector(kind.surface);
    var dialog=el.querySelector(kind.dialog);
    var closeBtn=el.querySelector(kind.close);
    if(!trigger||!surface||!dialog)return;
    var modal=kind.modal;
    var mode=el.getAttribute('data-trigger')||'click';
    var once=el.getAttribute('data-once')||'session';
    var key='quizably_seen_'+el.getAttribute('data-quizably-embed')+'_'+(el.getAttribute('data-uuid')||'');
    var returnTo=null;
    var prevOverflow='';
    function isOpen(){return !surface.hasAttribute('hidden');}
    function focusables(){
      return Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE),function(n){return n.offsetParent!==null||n===document.activeElement;});
    }
    function onKey(e){
      if(e.key==='Escape'||e.key==='Esc'){close();return;}
      if(e.key!=='Tab'||!modal)return;
      var f=focusables();
      if(!f.length){e.preventDefault();dialog.focus();return;}
      var first=f[0],last=f[f.length-1],active=document.activeElement;
      if(!dialog.contains(active)){e.preventDefault();first.focus();}
      else if(e.shiftKey&&(active===first||active===dialog)){e.preventDefault();last.focus();}
      else if(!e.shiftKey&&active===last){e.preventDefault();first.focus();}
    }
    function open(auto){
      if(isOpen())return;
      if(modal&&document.querySelector('.quizably-popup-overlay:not([hidden])'))return;
      var active=document.activeElement;
      returnTo=active&&active!==document.body?active:trigger;
      surface.removeAttribute('hidden');
      trigger.setAttribute('aria-expanded','true');
      if(modal){prevOverflow=document.body.style.overflow;document.body.style.overflow='hidden';}
      // A modal always takes focus. A slide-in is not modal, so an automatic open must not pull
      // focus away from what the visitor is doing; opened by the visitor it does take it.
      if(modal||!auto)dialog.focus();
      document.addEventListener('keydown',onKey);
    }
    function close(){
      if(!isOpen())return;
      var hadFocus=dialog.contains(document.activeElement);
      surface.setAttribute('hidden','');
      trigger.setAttribute('aria-expanded','false');
      if(modal)document.body.style.overflow=prevOverflow;
      document.removeEventListener('keydown',onKey);
      if((modal||hadFocus)&&returnTo&&document.contains(returnTo)&&returnTo.focus)returnTo.focus();
      returnTo=null;
    }
    function autoOpen(){
      if(capped(key,once))return;
      remember(key,once);
      open(true);
    }
    trigger.addEventListener('click',function(){if(modal||!isOpen()){open(false);}else{close();}});
    if(closeBtn)closeBtn.addEventListener('click',close);
    if(modal)surface.addEventListener('click',function(e){if(e.target===surface)close();});
    if(mode==='click'||capped(key,once))return;
    if(mode==='delay'){
      setTimeout(autoOpen,(parseFloat(el.getAttribute('data-delay'))||0)*1000);
    }else if(mode==='scroll'){
      var pct=parseFloat(el.getAttribute('data-scroll'))||50;
      var onScroll=function(){
        var max=document.documentElement.scrollHeight-window.innerHeight;
        if(max<=0)return;
        var y=window.pageYOffset||document.documentElement.scrollTop||0;
        if(y/max*100>=pct){window.removeEventListener('scroll',onScroll);autoOpen();}
      };
      window.addEventListener('scroll',onScroll,{passive:true});
      onScroll();
    }else if(mode==='exit'){
      // Exit intent needs a pointer that can leave through the top of the window: a mouse or
      // trackpad. Touch-only devices have none, so it never fires there.
      if(window.matchMedia&&window.matchMedia('(hover: hover) and (pointer: fine)').matches){
        var onOut=function(e){
          if(e.relatedTarget===null&&e.clientY<=0){document.removeEventListener('mouseout',onOut);autoOpen();}
        };
        document.addEventListener('mouseout',onOut);
      }
    }
  }
  document.querySelectorAll('[data-quizably-embed]').forEach(init);
})();
JS;
    }
}
