<?php
namespace Quizably\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Static catalog of 30 predefined quiz presets.
 *
 * Each preset contains full question/answer/result data so it can be
 * imported atomically by PresetController without extra API calls.
 *
 * Presets 1-18 are free (classic / minimal templates, free types).
 * Presets 19-30 require the Pro plugin (Pro templates or Pro types).
 */
final class PresetLibrary
{
    /** @return array<int,array> All presets including full question/result data. */
    public static function all(): array
    {
        return [
            self::coffee_personality(),
            self::travel_style(),
            self::season_personality(),
            self::productivity_style(),
            self::superhero_archetype(),
            self::learning_style(),
            self::world_geography(),
            self::science_myths(),
            self::pop_culture_2024(),
            self::movie_night(),
            self::football_legends(),
            self::product_feedback(),
            self::team_culture(),
            self::event_satisfaction(),
            self::customer_pulse(),
            self::js_framework_poll(),
            self::remote_office_poll(),
            self::social_platform_poll(),
            // Pro presets
            self::money_personality(),
            self::career_path(),
            self::space_trivia(),
            self::sports_championship(),
            self::marketing_maturity(),
            self::leadership_style(),
            self::product_finder(),
            self::it_support_triage(),
            self::employee_wellbeing(),
            self::content_creator_intake(),
            self::writing_style(),
            self::dev_tools_poll(),
        ];
    }

    /**
     * Find a single preset by its id slug.
     *
     * @param string $id
     * @return array|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $preset) {
            if (($preset['id'] ?? '') === $id) {
                return $preset;
            }
        }
        return null;
    }

    /**
     * Build the URL for a preset image bundled in the plugin itself.
     *
     * Every preset thumbnail and question/result media image used to hotlink
     * images.unsplash.com directly — flagged by WordPress.org's first-submission
     * review (Guideline 6: don't call remote files that aren't providing an
     * actual service). All 38 distinct photos are now downloaded once and
     * shipped under assets/images/presets/, referenced from here instead.
     *
     * @param string $filename e.g. 'photo-1495474472287-4d71bcdd2085.jpg'
     */
    private static function img(string $filename): string
    {
        if ( ! defined('QUIZABLY_PLUGIN_URL')) {
            return '';
        }
        return QUIZABLY_PLUGIN_URL . 'assets/images/presets/' . $filename;
    }

    // -------------------------------------------------------------------------
    // Helper constructors
    // -------------------------------------------------------------------------

    /** Build a personality answer (maps to a result). */
    private static function pa(string $label, string $rid, int $pos): array
    {
        return ['label' => $label, 'value' => sanitize_title($label), 'personality_result_id' => $rid, 'position' => $pos];
    }

    /** Build a trivia answer. */
    private static function ta(string $label, bool $correct, int $pos): array
    {
        return ['label' => $label, 'value' => sanitize_title($label), 'is_correct' => $correct ? 1 : 0, 'points' => $correct ? 1 : 0, 'position' => $pos];
    }

    /** Build a plain answer (survey / poll). */
    private static function sa(string $label, int $pos): array
    {
        return ['label' => $label, 'value' => sanitize_title($label), 'position' => $pos];
    }

    /** Build a personality result. */
    private static function pr(string $id, string $title, string $content, int $pos, string $img = ''): array
    {
        return ['id' => $id, 'title' => $title, 'content' => $content, 'image_url' => $img, 'position' => $pos];
    }

    /** Build a score-banded trivia result. */
    private static function tr(string $id, string $title, string $content, int $min, int $max, int $pos): array
    {
        return ['id' => $id, 'title' => $title, 'content' => $content, 'image_url' => '', 'score_min' => $min, 'score_max' => $max, 'position' => $pos];
    }

    /** Build a survey / poll result (single thank-you). */
    private static function sr(string $title, string $content): array
    {
        return ['id' => 'r-done', 'title' => $title, 'content' => $content, 'image_url' => '', 'position' => 0];
    }

    /** Build a single-choice question. */
    private static function sq(string $title, array $answers, int $pos, bool $required = true, string $img = ''): array
    {
        $q = ['type' => 'single', 'title' => $title, 'required' => $required, 'position' => $pos, 'answers' => $answers];
        if ($img !== '') $q['media_url'] = $img;
        return $q;
    }

    /** Build a multi-choice question. */
    private static function mq(string $title, array $answers, int $pos, bool $required = false, string $img = ''): array
    {
        $q = ['type' => 'multi', 'title' => $title, 'required' => $required, 'position' => $pos, 'answers' => $answers];
        if ($img !== '') $q['media_url'] = $img;
        return $q;
    }

    /** Build a true/false question. */
    private static function tfq(string $title, bool $answer_is_true, int $pos, string $img = ''): array
    {
        $q = [
            'type' => 'true_false', 'title' => $title, 'required' => true, 'position' => $pos,
            'answers' => [
                ['label' => 'True',  'value' => 'true',  'is_correct' => $answer_is_true  ? 1 : 0, 'points' => $answer_is_true  ? 1 : 0, 'position' => 0],
                ['label' => 'False', 'value' => 'false', 'is_correct' => !$answer_is_true ? 1 : 0, 'points' => !$answer_is_true ? 1 : 0, 'position' => 1],
            ],
        ];
        if ($img !== '') $q['media_url'] = $img;
        return $q;
    }

    /** Build a rating question. */
    private static function rq(string $title, int $pos, bool $required = true, string $img = ''): array
    {
        $q = ['type' => 'rating', 'title' => $title, 'required' => $required, 'position' => $pos, 'answers' => []];
        if ($img !== '') $q['media_url'] = $img;
        return $q;
    }

    /**
     * Build a text question. The type is `short_text`: the name the builder, the
     * quiz screens and the scorer all use. (It used to be written as `text`, which
     * nothing in the free plugin renders - those questions showed an empty list.)
     */
    private static function tq(string $title, int $pos, bool $required = false, string $img = ''): array
    {
        $q = ['type' => 'short_text', 'title' => $title, 'required' => $required, 'position' => $pos, 'answers' => []];
        if ($img !== '') $q['media_url'] = $img;
        return $q;
    }

    // -------------------------------------------------------------------------
    // FREE PRESETS — personality
    // -------------------------------------------------------------------------

    private static function coffee_personality(): array
    {
        return [
            'id'             => 'coffee-personality',
            'title'          => 'Which Coffee Are You?',
            'description'    => 'A fun personality quiz revealing your coffee spirit — pour over, espresso, latte, or cold brew.',
            'type'           => 'personality',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1495474472287-4d71bcdd2085.jpg'),
            'question_count' => 5,
            'result_count'   => 4,
            'tags'           => ['lifestyle', 'food'],
            'pro'            => false,
            'results' => [
                self::pr('r-pourover', 'You are a Pour Over', '<p>Patient, intentional, and a little nerdy about details. You make time for the good stuff — and everyone notices the difference.</p>', 0),
                self::pr('r-espresso', 'You are an Espresso', '<p>Short, bold, and relentless. You run on momentum and aren\'t afraid of a little bitterness along the way.</p>', 1),
                self::pr('r-latte',    'You are a Latte',     '<p>Warm, social, and comforting. People feel better after five minutes with you, and that\'s not an accident.</p>', 2),
                self::pr('r-cold',     'You are a Cold Brew', '<p>Low-key, steady, and secretly very caffeinated. You play the long game and win it.</p>', 3),
            ],
            'questions' => [
                self::sq('Pick your ideal morning ritual.', [
                    self::pa('Slow pour, quiet house, no rush', 'r-pourover', 0),
                    self::pa('Straight to the machine — no waiting', 'r-espresso', 1),
                    self::pa('Something warm and slow to start', 'r-latte', 2),
                    self::pa('Made it the night before, already chilled', 'r-cold', 3),
                ], 0),
                self::sq('How do you prefer to work?', [
                    self::pa('Deep focus — one task at a time', 'r-pourover', 0),
                    self::pa('Sprint hard, crash later, repeat', 'r-espresso', 1),
                    self::pa('Calls, collabs, and constant conversation', 'r-latte', 2),
                    self::pa('Work anywhere, anytime, on my own terms', 'r-cold', 3),
                ], 1),
                self::sq('Your ideal weekend looks like...', [
                    self::pa('A long book and total silence', 'r-pourover', 0),
                    self::pa('Three events in a single day', 'r-espresso', 1),
                    self::pa('Brunch with friends, no plans after', 'r-latte', 2),
                    self::pa('A long hike or a cold swim', 'r-cold', 3),
                ], 2),
                self::sq('When stress peaks, you...', [
                    self::pa('Slow down and think it through carefully', 'r-pourover', 0),
                    self::pa('Push harder until it\'s done', 'r-espresso', 1),
                    self::pa('Call someone who calms you down', 'r-latte', 2),
                    self::pa('Take a long walk and reset completely', 'r-cold', 3),
                ], 3),
                self::sq('Describe your perfect evening.', [
                    self::pa('Quiet dinner, early night, full sleep', 'r-pourover', 0),
                    self::pa('Still going strong at midnight', 'r-espresso', 1),
                    self::pa('Wine, good company, no agenda', 'r-latte', 2),
                    self::pa('Outside somewhere, moving, exploring', 'r-cold', 3),
                ], 4),
            ],
        ];
    }

    private static function travel_style(): array
    {
        return [
            'id'             => 'travel-style',
            'title'          => "What's Your Travel Style?",
            'description'    => 'Discover your travel personality — adventure seeker, culture lover, beach bum, or city explorer.',
            'type'           => 'personality',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1488646953014-85cb44e25828.jpg'),
            'question_count' => 4,
            'result_count'   => 4,
            'tags'           => ['travel', 'lifestyle'],
            'pro'            => false,
            'results' => [
                self::pr('r-adventure', 'The Adventure Seeker', '<p>You crave peaks, trails, and raw experiences. Comfort is optional; memories are mandatory.</p>', 0),
                self::pr('r-culture',   'The Culture Lover',    '<p>History, art, and local stories fuel every trip. You return home changed every time.</p>', 1),
                self::pr('r-beach',     'The Beach Bum',        '<p>Sun, sand, and doing absolutely nothing — that\'s the point. You have zero regrets.</p>', 2),
                self::pr('r-urban',     'The City Explorer',    '<p>Streets, street food, and neighborhoods are your playground. You live for the energy.</p>', 3),
            ],
            'questions' => [
                self::sq('Pick your dream destination type.', [
                    self::pa('Mountain wilderness with no signal', 'r-adventure', 0),
                    self::pa('Ancient ruins and UNESCO sites', 'r-culture', 1),
                    self::pa('A tropical beach with warm water', 'r-beach', 2),
                    self::pa('A buzzing city skyline at night', 'r-urban', 3),
                ], 0),
                self::sq('Where would you rather stay?', [
                    self::pa('A tent, hostel, or off-grid cabin', 'r-adventure', 0),
                    self::pa('A boutique hotel in the old town', 'r-culture', 1),
                    self::pa('An overwater resort or beachfront villa', 'r-beach', 2),
                    self::pa('A stylish apartment in the city center', 'r-urban', 3),
                ], 1),
                self::sq('First thing you do when you land.', [
                    self::pa('Head straight for the trailhead', 'r-adventure', 0),
                    self::pa('Visit the nearest museum or landmark', 'r-culture', 1),
                    self::pa('Drop bags, find the nearest beach', 'r-beach', 2),
                    self::pa('Walk the neighborhood, find local food', 'r-urban', 3),
                ], 2),
                self::sq("What's your must-have travel item?", [
                    self::pa('Sturdy hiking boots', 'r-adventure', 0),
                    self::pa('A good camera and a guidebook', 'r-culture', 1),
                    self::pa('Sunscreen and a great playlist', 'r-beach', 2),
                    self::pa('A curated restaurant map', 'r-urban', 3),
                ], 3),
            ],
        ];
    }

    private static function season_personality(): array
    {
        return [
            'id'             => 'season-personality',
            'title'          => 'What Season Matches Your Personality?',
            'description'    => 'Which season matches your energy — vibrant spring, bold summer, cozy autumn, or quiet winter?',
            'type'           => 'personality',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1507003211169-0a1dd7228f2d.jpg'),
            'question_count' => 4,
            'result_count'   => 4,
            'tags'           => ['lifestyle', 'personality'],
            'pro'            => false,
            'results' => [
                self::pr('r-spring', 'Spring', '<p>You bring new energy wherever you go. You love fresh starts, growth, and blooming possibilities.</p>', 0),
                self::pr('r-summer', 'Summer', '<p>Bold, warm, and full of life. You live in the moment and make every day feel like a celebration.</p>', 1),
                self::pr('r-autumn', 'Autumn', '<p>Thoughtful, warm-toned, and beautifully complex. You find magic in change and depth in everything.</p>', 2),
                self::pr('r-winter', 'Winter', '<p>Quiet, sharp, and deeply focused. Beneath the calm surface, there\'s a warmth that only a few get to see.</p>', 3),
            ],
            'questions' => [
                self::sq('Pick the outdoor setting that calls to you most.', [
                    self::pa('A field of wildflowers after rain', 'r-spring', 0),
                    self::pa('A wide open beach on a hot day', 'r-summer', 1),
                    self::pa('A forest trail covered in falling leaves', 'r-autumn', 2),
                    self::pa('A snowy landscape, silent and still', 'r-winter', 3),
                ], 0),
                self::sq('How do you recharge after a long week?', [
                    self::pa('Try something new — a class, a place, a project', 'r-spring', 0),
                    self::pa('Get outside and move — hike, swim, or play', 'r-summer', 1),
                    self::pa('Cozy up with a book, tea, and soft lighting', 'r-autumn', 2),
                    self::pa('Solitude and silence — completely alone time', 'r-winter', 3),
                ], 1),
                self::sq('How would friends describe your energy?', [
                    self::pa('Always excited, always evolving', 'r-spring', 0),
                    self::pa('High-energy, fun, and infectious', 'r-summer', 1),
                    self::pa('Warm, thoughtful, and grounding', 'r-autumn', 2),
                    self::pa('Calm, deep, and quietly powerful', 'r-winter', 3),
                ], 2),
                self::sq('Pick your ideal work style.', [
                    self::pa('Fresh projects, new ideas, constant growth', 'r-spring', 0),
                    self::pa('High energy, collaboration, and momentum', 'r-summer', 1),
                    self::pa('Wrapping things up thoughtfully and well', 'r-autumn', 2),
                    self::pa('Deep focus, long stretches, minimal interruption', 'r-winter', 3),
                ], 3),
            ],
        ];
    }

    private static function productivity_style(): array
    {
        return [
            'id'             => 'productivity-style',
            'title'          => "What's Your Productivity Style?",
            'description'    => 'Find out how you get things done — deep worker, sprint planner, collaborator, or creative chaos.',
            'type'           => 'personality',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1483058712412-4245e9b90334.jpg'),
            'question_count' => 5,
            'result_count'   => 4,
            'tags'           => ['productivity', 'business'],
            'pro'            => false,
            'results' => [
                self::pr('r-deep',   'The Deep Worker',    '<p>You do your best work in long, uninterrupted blocks. Flow state is your competitive advantage.</p>', 0),
                self::pr('r-sprint', 'The Sprint Planner', '<p>Deadlines are your fuel. You batch, time-box, and sprint to the finish line every time.</p>', 1),
                self::pr('r-collab', 'The Collaborator',   '<p>You think out loud and thrive with others. Your best ideas come from dialogue, not solitude.</p>', 2),
                self::pr('r-chaos',  'The Creative Chaos', '<p>Your process looks messy from the outside — but the results speak for themselves.</p>', 3),
            ],
            'questions' => [
                self::sq('Pick your ideal work environment.', [
                    self::pa('Quiet office or home setup — no distractions', 'r-deep', 0),
                    self::pa('Any space with a timer and a clear goal', 'r-sprint', 1),
                    self::pa('Open floor with easy access to my team', 'r-collab', 2),
                    self::pa('Wherever inspiration strikes — café, couch, kitchen', 'r-chaos', 3),
                ], 0),
                self::sq('How do you handle your to-do list?', [
                    self::pa('Pick one important thing and protect it all day', 'r-deep', 0),
                    self::pa('Time-block everything, review at end of day', 'r-sprint', 1),
                    self::pa('Share it with someone — accountability helps', 'r-collab', 2),
                    self::pa('It lives in my head and works out fine', 'r-chaos', 3),
                ], 1),
                self::sq('What breaks your focus the most?', [
                    self::pa('Interruptions — any kind, any time', 'r-deep', 0),
                    self::pa('Running out of time or missing a deadline', 'r-sprint', 1),
                    self::pa('Working alone with no feedback loop', 'r-collab', 2),
                    self::pa('Being forced to follow a rigid structure', 'r-chaos', 3),
                ], 2),
                self::sq('When do you feel most productive?', [
                    self::pa('Early morning, deep in a single task', 'r-deep', 0),
                    self::pa('In a 90-minute focused sprint', 'r-sprint', 1),
                    self::pa('During a brainstorming session with my team', 'r-collab', 2),
                    self::pa('Late at night when everyone else has given up', 'r-chaos', 3),
                ], 3),
                self::sq('How do you handle a big project deadline?', [
                    self::pa('Start weeks early, work in calm focused sessions', 'r-deep', 0),
                    self::pa('Break it into sprints, hit each mini-milestone', 'r-sprint', 1),
                    self::pa('Rally the team, divide and conquer together', 'r-collab', 2),
                    self::pa('My best work comes in the final 48 hours', 'r-chaos', 3),
                ], 4),
            ],
        ];
    }

    private static function superhero_archetype(): array
    {
        return [
            'id'             => 'superhero-archetype',
            'title'          => 'Which Superhero Archetype Are You?',
            'description'    => 'Are you the guardian, the avenger, the mastermind, or the wild card? Find your archetype.',
            'type'           => 'personality',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1531259683007-016a7b628fc3.jpg'),
            'question_count' => 4,
            'result_count'   => 4,
            'tags'           => ['fun', 'pop-culture'],
            'pro'            => false,
            'results' => [
                self::pr('r-guardian',   'The Guardian',   '<p>You protect what matters most. Your power comes from loyalty, strength, and an unshakeable moral core.</p>', 0),
                self::pr('r-avenger',    'The Avenger',    '<p>Driven by justice. You don\'t wait for someone else to fix things — you step in, no matter the cost.</p>', 1),
                self::pr('r-mastermind', 'The Mastermind', '<p>Three steps ahead at all times. Your greatest superpower is your mind — and knowing how to use it.</p>', 2),
                self::pr('r-wildcard',   'The Wild Card',  '<p>Unpredictable, fearless, and somehow it always works out. Rules were made for other people.</p>', 3),
            ],
            'questions' => [
                self::sq('If you had one power, what would it be?', [
                    self::pa('Indestructibility — nothing can stop me', 'r-guardian', 0),
                    self::pa('Super strength to fight any threat head-on', 'r-avenger', 1),
                    self::pa('Genius-level intelligence and foresight', 'r-mastermind', 2),
                    self::pa('Shapeshifting — complete unpredictability', 'r-wildcard', 3),
                ], 0),
                self::sq('Your biggest enemy has arrived. You...', [
                    self::pa('Stand between them and the people I protect', 'r-guardian', 0),
                    self::pa('Charge in — anger is fuel', 'r-avenger', 1),
                    self::pa('Execute the plan I prepared months ago', 'r-mastermind', 2),
                    self::pa('Wing it — I always figure it out mid-fight', 'r-wildcard', 3),
                ], 1),
                self::sq('What is your secret identity?', [
                    self::pa('A quiet professional who lives for others', 'r-guardian', 0),
                    self::pa('Someone with personal stakes and a grudge', 'r-avenger', 1),
                    self::pa('A billionaire, scientist, or genius hiding in plain sight', 'r-mastermind', 2),
                    self::pa('Nobody knows — not even me sometimes', 'r-wildcard', 3),
                ], 2),
                self::sq('What drives your mission?', [
                    self::pa('Protecting the innocent — it\'s a responsibility', 'r-guardian', 0),
                    self::pa('Making wrongs right — no matter what it takes', 'r-avenger', 1),
                    self::pa('A long-term plan to fix the root cause', 'r-mastermind', 2),
                    self::pa('Chaos is fun — and good usually comes from it', 'r-wildcard', 3),
                ], 3),
            ],
        ];
    }

    private static function learning_style(): array
    {
        return [
            'id'             => 'learning-style',
            'title'          => "What's Your Learning Style?",
            'description'    => 'Discover how you absorb information best — visual, auditory, reading/writing, or kinesthetic.',
            'type'           => 'personality',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1434030216411-0b793f4b4173.jpg'),
            'question_count' => 4,
            'result_count'   => 4,
            'tags'           => ['education', 'personal-growth'],
            'pro'            => false,
            'results' => [
                self::pr('r-visual',      'Visual Learner',        '<p>Charts, diagrams, and color-coded notes are your jam. You think in pictures and learn best when you can see it.</p>', 0),
                self::pr('r-auditory',    'Auditory Learner',      '<p>Podcasts, lectures, and talking things through help ideas click for you. Sound is your learning superpower.</p>', 1),
                self::pr('r-reading',     'Reading/Writing Learner','<p>Books, notes, and written summaries are how you make things stick. You learn by reading and writing it down.</p>', 2),
                self::pr('r-kinesthetic', 'Kinesthetic Learner',   '<p>You learn by doing. Hands-on practice, prototypes, and real-world experiments are where you shine.</p>', 3),
            ],
            'questions' => [
                self::sq('You need to learn a new skill. You start by...', [
                    self::pa('Watching a walkthrough video or tutorial', 'r-visual', 0),
                    self::pa('Listening to a podcast or recorded lecture', 'r-auditory', 1),
                    self::pa('Reading a book, guide, or detailed article', 'r-reading', 2),
                    self::pa('Diving in and trying it hands-on immediately', 'r-kinesthetic', 3),
                ], 0),
                self::sq("When you're solving a hard problem, you...", [
                    self::pa('Draw it out — a diagram or whiteboard helps', 'r-visual', 0),
                    self::pa('Talk it through out loud, even to myself', 'r-auditory', 1),
                    self::pa('Write out the problem and possible solutions', 'r-reading', 2),
                    self::pa('Build a prototype or try different approaches', 'r-kinesthetic', 3),
                ], 1),
                self::sq('How do you best remember information from a meeting?', [
                    self::pa('I sketched visuals or a mind map during it', 'r-visual', 0),
                    self::pa('I remember what was said — I replay it mentally', 'r-auditory', 1),
                    self::pa('I took detailed written notes', 'r-reading', 2),
                    self::pa('I remember what I did, not what was discussed', 'r-kinesthetic', 3),
                ], 2),
                self::sq('If you had to teach something to someone else, you\'d...', [
                    self::pa('Create a visual presentation or diagram', 'r-visual', 0),
                    self::pa('Explain it through conversation and examples', 'r-auditory', 1),
                    self::pa('Write a step-by-step guide for them', 'r-reading', 2),
                    self::pa('Walk them through it hands-on together', 'r-kinesthetic', 3),
                ], 3),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // FREE PRESETS — trivia
    // -------------------------------------------------------------------------

    private static function world_geography(): array
    {
        return [
            'id'             => 'world-geography',
            'title'          => 'World Geography Speed Run',
            'description'    => 'Test your world geography knowledge — capitals, rivers, countries, and fascinating facts.',
            'type'           => 'trivia',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1524661135-423995f22d0b.jpg'),
            'question_count' => 6,
            'result_count'   => 3,
            'tags'           => ['geography', 'education'],
            'pro'            => false,
            'results' => [
                self::tr('r-low',  'Explorer',      '<p>A rough round! Geography is a big world — keep exploring and try again.</p>', 0, 2, 0),
                self::tr('r-mid',  'Navigator',     '<p>Solid knowledge! You clearly pay attention to the world around you.</p>', 3, 4, 1),
                self::tr('r-high', 'Atlas Master',  '<p>Clean sweep! You could navigate without GPS — impressive geography instincts.</p>', 5, 6, 2),
            ],
            'questions' => [
                self::sq('Which country has the most natural lakes?', [
                    self::ta('United States', false, 0),
                    self::ta('Canada', true, 1),
                    self::ta('Russia', false, 2),
                    self::ta('Finland', false, 3),
                ], 0),
                self::sq('What is the capital of Australia?', [
                    self::ta('Sydney', false, 0),
                    self::ta('Melbourne', false, 1),
                    self::ta('Canberra', true, 2),
                    self::ta('Brisbane', false, 3),
                ], 1),
                self::sq('The Nile River flows primarily in which direction?', [
                    self::ta('South', false, 0),
                    self::ta('North', true, 1),
                    self::ta('East', false, 2),
                    self::ta('West', false, 3),
                ], 2),
                self::sq('Which is the largest ocean by area?', [
                    self::ta('Atlantic', false, 0),
                    self::ta('Indian', false, 1),
                    self::ta('Arctic', false, 2),
                    self::ta('Pacific', true, 3),
                ], 3),
                self::sq('Mount Everest lies on the border of Nepal and which other country?', [
                    self::ta('India', false, 0),
                    self::ta('Bhutan', false, 1),
                    self::ta('China (Tibet)', true, 2),
                    self::ta('Pakistan', false, 3),
                ], 4),
                self::tfq('Australia is both a country and a continent.', true, 5),
            ],
        ];
    }

    private static function science_myths(): array
    {
        return [
            'id'             => 'science-myths',
            'title'          => 'Science Myths vs Facts',
            'description'    => 'Separate fact from fiction — can you bust these popular science myths?',
            'type'           => 'trivia',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1532187863486-abf9dbad1b69.jpg'),
            'question_count' => 6,
            'result_count'   => 3,
            'tags'           => ['science', 'education'],
            'pro'            => false,
            'results' => [
                self::tr('r-low',  'Skeptic',      '<p>Science is tricky! Many of these are common misconceptions. Keep questioning everything.</p>', 0, 2, 0),
                self::tr('r-mid',  'Curious Mind', '<p>You spotted several myths! You clearly think critically about what you hear.</p>', 3, 4, 1),
                self::tr('r-high', 'Science Nerd', '<p>Near-perfect score! You are not fooled by common myths. Impressive scientific literacy.</p>', 5, 6, 2),
            ],
            'questions' => [
                self::tfq('Humans use only 10% of their brain.', false, 0),
                self::sq('Which planet is closest to Earth on average?', [
                    self::ta('Venus', false, 0),
                    self::ta('Mars', false, 1),
                    self::ta('Mercury', true, 2),
                    self::ta('Jupiter', false, 3),
                ], 1),
                self::tfq('Lightning never strikes the same place twice.', false, 2),
                self::sq('What percentage of the ocean has been explored by humans?', [
                    self::ta('About 50%', false, 0),
                    self::ta('About 80%', false, 1),
                    self::ta('Less than 20%', true, 2),
                    self::ta('Nearly 100%', false, 3),
                ], 3),
                self::tfq('Bats are completely blind.', false, 4),
                self::sq('What is the hardest natural substance on Earth?', [
                    self::ta('Granite', false, 0),
                    self::ta('Quartz', false, 1),
                    self::ta('Diamond', true, 2),
                    self::ta('Titanium', false, 3),
                ], 5),
            ],
        ];
    }

    private static function pop_culture_2024(): array
    {
        return [
            'id'             => 'pop-culture-2024',
            'title'          => 'Pop Culture Trivia 2024',
            'description'    => 'How well do you know the biggest moments in movies, music, gaming, and streaming?',
            'type'           => 'trivia',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1489599849927-2ee91cede3ba.jpg'),
            'question_count' => 6,
            'result_count'   => 3,
            'tags'           => ['entertainment', 'pop-culture'],
            'pro'            => false,
            'results' => [
                self::tr('r-low',  'Casual Viewer', '<p>Not everyone keeps up with everything — and that\'s fine. There\'s so much good stuff out there!</p>', 0, 2, 0),
                self::tr('r-mid',  'Pop Fan',       '<p>You\'re in the loop on most things. You clearly enjoy staying connected to what\'s happening.</p>', 3, 4, 1),
                self::tr('r-high', 'Trivia Wizard', '<p>You are the person everyone turns to during movie night debates. Encyclopedic knowledge!</p>', 5, 6, 2),
            ],
            'questions' => [
                self::sq('Which 2023 film broke box-office records and had a cultural moment all its own?', [
                    self::ta('Oppenheimer', false, 0),
                    self::ta('Barbie', true, 1),
                    self::ta('Dune: Part Two', false, 2),
                    self::ta('The Marvels', false, 3),
                ], 0),
                self::sq('Taylor Swift\'s record-breaking tour was called...', [
                    self::ta('The Midnight Tour', false, 0),
                    self::ta('The Eras Tour', true, 1),
                    self::ta('The Fearless Tour', false, 2),
                    self::ta('The Reputation Tour', false, 3),
                ], 1),
                self::sq('Which streaming series became a global phenomenon with its survival game plot?', [
                    self::ta('Succession', false, 0),
                    self::ta('The Last of Us', false, 1),
                    self::ta('Squid Game', true, 2),
                    self::ta('Severance', false, 3),
                ], 2),
                self::tfq('The game "Baldur\'s Gate 3" won Game of the Year at The Game Awards 2023.', true, 3),
                self::sq('Which artist had the best-selling album globally in 2023?', [
                    self::ta('Beyoncé', false, 0),
                    self::ta('Morgan Wallen', false, 1),
                    self::ta('Taylor Swift', true, 2),
                    self::ta('SZA', false, 3),
                ], 4),
                self::tfq('"Oppenheimer" won the Academy Award for Best Picture in 2024.', true, 5),
            ],
        ];
    }

    private static function movie_night(): array
    {
        return [
            'id'             => 'movie-night',
            'title'          => 'Movie Night Quiz',
            'description'    => 'Lights, camera, action! Test your movie knowledge across genres and decades.',
            'type'           => 'trivia',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1512070679279-8988d32161be.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['movies', 'entertainment'],
            'pro'            => false,
            'results' => [
                self::tr('r-low',  'Popcorn Fan',  '<p>You\'re here for the snacks more than the trivia — and honestly, same. Keep watching great films!</p>', 0, 1, 0),
                self::tr('r-mid',  'Film Buff',    '<p>Solid movie knowledge! You clearly spend quality time in front of the screen.</p>', 2, 3, 1),
                self::tr('r-high', 'Cinephile',    '<p>Outstanding! You should be writing reviews — your film knowledge is top tier.</p>', 4, 5, 2),
            ],
            'questions' => [
                self::sq('Who directed "Inception" (2010)?', [
                    self::ta('Steven Spielberg', false, 0),
                    self::ta('Christopher Nolan', true, 1),
                    self::ta('Ridley Scott', false, 2),
                    self::ta('Denis Villeneuve', false, 3),
                ], 0),
                self::sq('Which film won the first-ever Academy Award for Best Picture in 1928?', [
                    self::ta('The Jazz Singer', false, 0),
                    self::ta('Sunrise', false, 1),
                    self::ta('Wings', true, 2),
                    self::ta('All Quiet on the Western Front', false, 3),
                ], 1),
                self::tfq('"The Dark Knight" (2008) was directed by Christopher Nolan.', true, 2),
                self::sq('Which actor plays Tony Stark in the Marvel Cinematic Universe?', [
                    self::ta('Chris Evans', false, 0),
                    self::ta('Robert Downey Jr.', true, 1),
                    self::ta('Chris Pratt', false, 2),
                    self::ta('Mark Ruffalo', false, 3),
                ], 3),
                self::sq('The phrase "Here\'s looking at you, kid" comes from which classic film?', [
                    self::ta('Gone with the Wind', false, 0),
                    self::ta('Citizen Kane', false, 1),
                    self::ta('Casablanca', true, 2),
                    self::ta('Sunset Boulevard', false, 3),
                ], 4),
            ],
        ];
    }

    private static function football_legends(): array
    {
        return [
            'id'             => 'football-legends',
            'title'          => 'Football Legends Challenge',
            'description'    => 'How much do you know about the beautiful game and its greatest players and moments?',
            'type'           => 'trivia',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1629217855633-79a6925d6c47.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['sports', 'football'],
            'pro'            => false,
            'results' => [
                self::tr('r-low',  'Armchair Fan',     '<p>You watch the highlights. That counts! Keep tuning in and the knowledge will come.</p>', 0, 1, 0),
                self::tr('r-mid',  'Match Regular',    '<p>Solid football knowledge — you clearly know your players and your history.</p>', 2, 3, 1),
                self::tr('r-high', 'Football Genius',  '<p>Exceptional! You could commentate a match from memory. The sport runs in your blood.</p>', 4, 5, 2),
            ],
            'questions' => [
                self::sq('Which player has won the most Ballon d\'Or awards?', [
                    self::ta('Cristiano Ronaldo', false, 0),
                    self::ta('Pelé', false, 1),
                    self::ta('Lionel Messi', true, 2),
                    self::ta('Zinedine Zidane', false, 3),
                ], 0),
                self::sq('Which country won the FIFA World Cup in 2022?', [
                    self::ta('Brazil', false, 0),
                    self::ta('France', false, 1),
                    self::ta('Argentina', true, 2),
                    self::ta('Germany', false, 3),
                ], 1),
                self::tfq('Pelé is the all-time top scorer in official international matches.', false, 2),
                self::sq('Which English club has won the most UEFA Champions League titles?', [
                    self::ta('Arsenal', false, 0),
                    self::ta('Chelsea', false, 1),
                    self::ta('Manchester United', false, 2),
                    self::ta('Liverpool', true, 3),
                ], 3),
                self::sq('The "Hand of God" goal was scored by which player?', [
                    self::ta('Ronaldo (R9)', false, 0),
                    self::ta('Diego Maradona', true, 1),
                    self::ta('Zinedine Zidane', false, 2),
                    self::ta('George Best', false, 3),
                ], 4),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // FREE PRESETS — survey
    // -------------------------------------------------------------------------

    private static function product_feedback(): array
    {
        return [
            'id'             => 'product-feedback',
            'title'          => 'Product Feedback Survey',
            'description'    => 'Collect structured customer feedback to improve your product or service.',
            'type'           => 'survey',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1556761175-4b46a572b786.jpg'),
            'question_count' => 5,
            'result_count'   => 1,
            'tags'           => ['business', 'feedback'],
            'pro'            => false,
            'results' => [self::sr('Thank you for your feedback!', '<p>Your responses help us improve. We really appreciate you taking the time to share.</p>')],
            'questions' => [
                self::rq('Overall, how satisfied are you with our product?', 0),
                self::sq('Which feature do you find most valuable?', [
                    self::sa('Ease of use', 0),
                    self::sa('Feature set', 1),
                    self::sa('Performance / speed', 2),
                    self::sa('Customer support', 3),
                ], 1),
                self::mq('Which areas would you like us to improve? (Select all that apply)', [
                    self::sa('User interface', 0),
                    self::sa('Documentation', 1),
                    self::sa('Pricing', 2),
                    self::sa('Integrations', 3),
                    self::sa('Mobile experience', 4),
                ], 2),
                self::sq('How likely are you to recommend us to a friend or colleague?', [
                    self::sa('Very likely', 0),
                    self::sa('Somewhat likely', 1),
                    self::sa('Neutral', 2),
                    self::sa('Unlikely', 3),
                ], 3),
                self::tq('Any other comments or suggestions for us?', 4),
            ],
        ];
    }

    private static function team_culture(): array
    {
        return [
            'id'             => 'team-culture',
            'title'          => 'Team Culture Check',
            'description'    => 'Measure team health with questions on collaboration, communication, and morale.',
            'type'           => 'survey',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1522071820081-009f0129c71c.jpg'),
            'question_count' => 5,
            'result_count'   => 1,
            'tags'           => ['business', 'hr'],
            'pro'            => false,
            'results' => [self::sr('Thanks for sharing!', '<p>Your honest feedback helps us build a better team culture. Responses are reviewed anonymously.</p>')],
            'questions' => [
                self::rq('How would you rate team morale overall?', 0),
                self::sq('How would you describe our team communication?', [
                    self::sa('Open and transparent', 0),
                    self::sa('Generally good', 1),
                    self::sa('Could be better', 2),
                    self::sa('Often unclear or siloed', 3),
                ], 1),
                self::sq('How well does your team collaborate across functions?', [
                    self::sa('Seamlessly — we work as one unit', 0),
                    self::sa('Well most of the time', 1),
                    self::sa('It depends on the team', 2),
                    self::sa('There are visible silos', 3),
                ], 2),
                self::rq('How would you rate your current work-life balance?', 3),
                self::sq("What's the biggest challenge your team faces right now?", [
                    self::sa('Too much on our plates', 0),
                    self::sa('Lack of clear direction', 1),
                    self::sa('Communication gaps', 2),
                    self::sa('Resource or tooling constraints', 3),
                ], 4),
            ],
        ];
    }

    private static function event_satisfaction(): array
    {
        return [
            'id'             => 'event-satisfaction',
            'title'          => 'Event Satisfaction Survey',
            'description'    => 'Gather post-event feedback to improve your future events and experiences.',
            'type'           => 'survey',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1540575467063-178a50c2df87.jpg'),
            'question_count' => 4,
            'result_count'   => 1,
            'tags'           => ['events', 'business'],
            'pro'            => false,
            'results' => [self::sr("Thanks for attending!", '<p>We\'re grateful you joined us. Your feedback will shape our next event directly.</p>')],
            'questions' => [
                self::rq('How would you rate the overall event experience?', 0),
                self::sq('How would you describe the content quality?', [
                    self::sa('Excellent — exceeded expectations', 0),
                    self::sa('Good — mostly relevant', 1),
                    self::sa('Mixed — some parts missed the mark', 2),
                    self::sa('Disappointing — not what I expected', 3),
                ], 1),
                self::sq('How was the event format and logistics?', [
                    self::sa('Very well organized', 0),
                    self::sa('Generally smooth', 1),
                    self::sa('Some hiccups but manageable', 2),
                    self::sa('Disorganized', 3),
                ], 2),
                self::tq('What would make future events even better?', 3),
            ],
        ];
    }

    private static function customer_pulse(): array
    {
        return [
            'id'             => 'customer-pulse',
            'title'          => 'Quick Customer Pulse',
            'description'    => 'A quick 3-question check-in on customer satisfaction and top priorities.',
            'type'           => 'survey',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1553729459-efe14ef6055d.jpg'),
            'question_count' => 3,
            'result_count'   => 1,
            'tags'           => ['business', 'feedback'],
            'pro'            => false,
            'results' => [self::sr('Thanks for the feedback!', '<p>These three answers help us focus on what matters most to you. We appreciate it!</p>')],
            'questions' => [
                self::rq('How satisfied are you with us overall?', 0),
                self::sq("What's the top benefit you get from us?", [
                    self::sa('Saves me time', 0),
                    self::sa('Saves me money', 1),
                    self::sa('Makes my work easier', 2),
                    self::sa("Helps me grow", 3),
                ], 1),
                self::tq('What one thing could we do better?', 2),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // FREE PRESETS — poll
    // -------------------------------------------------------------------------

    private static function js_framework_poll(): array
    {
        return [
            'id'             => 'js-framework-poll',
            'title'          => 'Best JavaScript Framework Poll',
            'description'    => 'Which JavaScript framework do you prefer? Cast your vote and see live results.',
            'type'           => 'poll',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1627398242454-45a1465c2479.jpg'),
            'question_count' => 1,
            'result_count'   => 1,
            'tags'           => ['tech', 'dev'],
            'pro'            => false,
            'results' => [self::sr('Results are in!', '<p>See how the community voted. Share this poll and keep the debate going!</p>')],
            'questions' => [
                self::sq('Which JavaScript framework is your daily driver?', [
                    self::sa('React', 0),
                    self::sa('Vue', 1),
                    self::sa('Angular', 2),
                    self::sa('Svelte', 3),
                    self::sa('Solid', 4),
                ], 0),
            ],
        ];
    }

    private static function remote_office_poll(): array
    {
        return [
            'id'             => 'remote-office-poll',
            'title'          => 'Remote vs Office Poll',
            'description'    => 'Remote, hybrid, or office? Share your preferred way of working.',
            'type'           => 'poll',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1497366216548-37526070297c.jpg'),
            'question_count' => 1,
            'result_count'   => 1,
            'tags'           => ['work', 'business'],
            'pro'            => false,
            'results' => [self::sr("Thanks for voting!", '<p>See where the crowd lands. The work world is changing — your vote is part of the story.</p>')],
            'questions' => [
                self::sq('What is your preferred work arrangement?', [
                    self::sa('Fully remote', 0),
                    self::sa('Hybrid (a few days in office)', 1),
                    self::sa('Fully in-office', 2),
                    self::sa('No preference — flexible is fine', 3),
                ], 0),
            ],
        ];
    }

    private static function social_platform_poll(): array
    {
        return [
            'id'             => 'social-platform-poll',
            'title'          => 'Favorite Social Platform Poll',
            'description'    => 'Which social media platform do you spend the most time on? Vote now!',
            'type'           => 'poll',
            'template'       => 'minimal',
            'thumbnail'      => self::img('photo-1611162617474-5b21e879e113.jpg'),
            'question_count' => 1,
            'result_count'   => 1,
            'tags'           => ['social', 'lifestyle'],
            'pro'            => false,
            'results' => [self::sr("Thanks for voting!", '<p>See where the community hangs out most. Share with friends to get more votes!</p>')],
            'questions' => [
                self::sq('Which social media platform do you use most?', [
                    self::sa('Instagram', 0),
                    self::sa('TikTok', 1),
                    self::sa('Twitter / X', 2),
                    self::sa('LinkedIn', 3),
                    self::sa('YouTube', 4),
                ], 0),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — personality
    // -------------------------------------------------------------------------

    private static function money_personality(): array
    {
        return [
            'id'             => 'money-personality',
            'title'          => "What's Your Money Personality?",
            'description'    => 'Uncover your money personality — are you a saver, investor, spender, or avoider?',
            'type'           => 'personality',
            'template'       => 'fullscreen',
            'thumbnail'      => self::img('photo-1579621970563-ebec7560ff3e.jpg'),
            'question_count' => 5,
            'result_count'   => 4,
            'tags'           => ['finance', 'lifestyle'],
            'pro'            => false, // personality on Full Screen: both are free
            'results' => [
                self::pr('r-saver',    'The Saver',    '<p>Security is your comfort zone. You find peace in a growing account and plan for every scenario — sometimes at the expense of living in the now.</p>', 0),
                self::pr('r-investor', 'The Investor', '<p>You think long-term and make your money work for you. Risk is just a variable to manage, not fear.</p>', 1),
                self::pr('r-spender',  'The Spender',  '<p>Life is for living and you live it fully. You are generous with yourself and others — building a budget might be your next great project.</p>', 2),
                self::pr('r-avoider',  'The Avoider',  '<p>Money stress is real, so you sometimes avoid it. The first step is the hardest, but facing your finances will unlock a new level of peace.</p>', 3),
            ],
            'questions' => [
                self::sq('You receive an unexpected $500. First instinct?', [
                    self::pa('Transfer it straight to savings', 'r-saver', 0),
                    self::pa('Add it to my investment account', 'r-investor', 1),
                    self::pa('Treat myself to something I\'ve been wanting', 'r-spender', 2),
                    self::pa('Leave it in checking and figure it out later', 'r-avoider', 3),
                ], 0),
                self::sq('How often do you review your finances?', [
                    self::pa('Weekly — I track everything', 'r-saver', 0),
                    self::pa('Monthly with a clear investment review', 'r-investor', 1),
                    self::pa('When I remember to — or when I have to', 'r-spender', 2),
                    self::pa('Rarely — it stresses me out', 'r-avoider', 3),
                ], 1),
                self::sq('What does money mean to you?', [
                    self::pa('Security and peace of mind', 'r-saver', 0),
                    self::pa('A tool for building long-term wealth', 'r-investor', 1),
                    self::pa('Freedom to enjoy life and share it', 'r-spender', 2),
                    self::pa('Mostly stress, honestly', 'r-avoider', 3),
                ], 2),
                self::sq('Your ideal purchase is...', [
                    self::pa('Something that holds its value', 'r-saver', 0),
                    self::pa('An asset that could appreciate', 'r-investor', 1),
                    self::pa('An experience or thing I\'ve always wanted', 'r-spender', 2),
                    self::pa('Whatever feels right in the moment', 'r-avoider', 3),
                ], 3),
                self::sq('How do you feel after a big purchase?', [
                    self::pa('Anxious — what if I needed that money?', 'r-saver', 0),
                    self::pa('Calculating — was the ROI worth it?', 'r-investor', 1),
                    self::pa('Great — I earned it and enjoyed it', 'r-spender', 2),
                    self::pa('I try not to think about it', 'r-avoider', 3),
                ], 4),
            ],
        ];
    }

    private static function career_path(): array
    {
        return [
            'id'             => 'career-path',
            'title'          => 'What Career Path Suits You?',
            'description'    => 'Find the career path that fits your strengths — creator, analyst, leader, or builder.',
            'type'           => 'personality',
            'template'       => 'cardstack',
            'thumbnail'      => self::img('photo-1454165804606-c3d57bc86b40.jpg'),
            'question_count' => 5,
            'result_count'   => 4,
            'tags'           => ['career', 'personal-growth'],
            'pro'            => true,
            'results' => [
                self::pr('r-creator',  'The Creator',  '<p>You thrive when given space to make something new — design, content, products, or experiences. Your work is your signature.</p>', 0),
                self::pr('r-analyst',  'The Analyst',  '<p>Data, systems, and patterns are your language. You bring clarity to complexity and make decisions that hold up.</p>', 1),
                self::pr('r-leader',   'The Leader',   '<p>You energize teams and move organizations forward. Your strength is in bringing out the best in everyone around you.</p>', 2),
                self::pr('r-builder',  'The Builder',  '<p>You love building things that work — systems, products, or companies. Execution is your superpower.</p>', 3),
            ],
            'questions' => [
                self::sq('In a team project, you naturally...', [
                    self::pa('Come up with the concepts and visuals', 'r-creator', 0),
                    self::pa('Dig into the data and find patterns', 'r-analyst', 1),
                    self::pa('Keep everyone aligned and motivated', 'r-leader', 2),
                    self::pa('Build the actual thing and ship it', 'r-builder', 3),
                ], 0),
                self::sq("What's your strongest natural skill?", [
                    self::pa('Imagination and originality', 'r-creator', 0),
                    self::pa('Critical thinking and research', 'r-analyst', 1),
                    self::pa('Communication and influence', 'r-leader', 2),
                    self::pa('Problem-solving and execution', 'r-builder', 3),
                ], 1),
                self::sq("What motivates you most at work?", [
                    self::pa('Making something beautiful or meaningful', 'r-creator', 0),
                    self::pa('Understanding how something really works', 'r-analyst', 1),
                    self::pa('Helping others grow and succeed', 'r-leader', 2),
                    self::pa('Shipping and seeing results', 'r-builder', 3),
                ], 2),
                self::sq('Your ideal day involves...', [
                    self::pa('A blank canvas and creative freedom', 'r-creator', 0),
                    self::pa('Deep research and clear findings', 'r-analyst', 1),
                    self::pa('Meetings, decisions, and team time', 'r-leader', 2),
                    self::pa('Shipping features and solving bugs', 'r-builder', 3),
                ], 3),
                self::sq('Your worst nightmare at work is...', [
                    self::pa('Being told exactly what to make with no freedom', 'r-creator', 0),
                    self::pa('Making big decisions without enough data', 'r-analyst', 1),
                    self::pa('Working alone with no one to collaborate with', 'r-leader', 2),
                    self::pa('Planning without ever shipping anything', 'r-builder', 3),
                ], 4),
            ],
        ];
    }

    private static function writing_style(): array
    {
        return [
            'id'             => 'writing-style',
            'title'          => 'What Writing Style Are You?',
            'description'    => 'Discover your writing voice — storyteller, analyst, poet, or journalist.',
            'type'           => 'personality',
            'template'       => 'magazine',
            'thumbnail'      => self::img('photo-1455390582262-044cdead277a.jpg'),
            'question_count' => 4,
            'result_count'   => 4,
            'tags'           => ['writing', 'creative'],
            'pro'            => true,
            'results' => [
                self::pr('r-storyteller', 'The Storyteller', '<p>You draw readers in with character, tension, and narrative arc. Every piece you write has a beginning, middle, and emotional end.</p>', 0),
                self::pr('r-analyst',     'The Analyst',     '<p>You write to inform and persuade with evidence. Your prose is clean, logical, and always backed by substance.</p>', 1),
                self::pr('r-poet',        'The Poet',        '<p>Language is your medium and sound is your instrument. Every word placement is intentional, every phrase resonant.</p>', 2),
                self::pr('r-journalist',  'The Journalist',  '<p>You cut to what matters. Clarity, brevity, and accuracy are your editorial commandments.</p>', 3),
            ],
            'questions' => [
                self::sq('When you start writing, what comes first?', [
                    self::pa('A character or scene I need to bring to life', 'r-storyteller', 0),
                    self::pa('My thesis or main argument', 'r-analyst', 1),
                    self::pa('A single image, word, or feeling', 'r-poet', 2),
                    self::pa('The key fact or hook that matters most', 'r-journalist', 3),
                ], 0),
                self::sq('You are happiest when writing about...', [
                    self::pa('People, relationships, and the human experience', 'r-storyteller', 0),
                    self::pa('Ideas, systems, and how things work', 'r-analyst', 1),
                    self::pa('Beauty, loss, and the in-between moments', 'r-poet', 2),
                    self::pa('Events, facts, and things that actually happened', 'r-journalist', 3),
                ], 1),
                self::sq('Your first draft usually looks like...', [
                    self::pa('A scene with too many characters to track', 'r-storyteller', 0),
                    self::pa('An outline with sub-points inside sub-points', 'r-analyst', 1),
                    self::pa('Fragments, images, and raw phrases', 'r-poet', 2),
                    self::pa('A lede and bullets that just need tightening', 'r-journalist', 3),
                ], 2),
                self::sq('Which writing rule feels most important to you?', [
                    self::pa('Show, don\'t tell', 'r-storyteller', 0),
                    self::pa('Cite your sources and make your case', 'r-analyst', 1),
                    self::pa('Sound matters as much as sense', 'r-poet', 2),
                    self::pa('Put the most important thing first', 'r-journalist', 3),
                ], 3),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — trivia
    // -------------------------------------------------------------------------

    private static function space_trivia(): array
    {
        return [
            'id'             => 'space-trivia',
            'title'          => 'Space Exploration Trivia',
            'description'    => 'Blast off with questions about space exploration, planets, and the cosmos.',
            'type'           => 'trivia',
            'template'       => 'splitscreen',
            'thumbnail'      => self::img('photo-1462331940025-496dfbfc7564.jpg'),
            'question_count' => 6,
            'result_count'   => 3,
            'tags'           => ['science', 'space'],
            'pro'            => true,
            'results' => [
                self::tr('r-low',  'Earthbound',   '<p>Space is big and mysterious — that\'s part of its beauty. Keep looking up and exploring!</p>', 0, 2, 0),
                self::tr('r-mid',  'Space Cadet',  '<p>Solid space knowledge! You clearly follow the latest missions and discoveries.</p>', 3, 4, 1),
                self::tr('r-high', 'Astronaut',    '<p>Mission accomplished. Your space IQ is out of this world — literally.</p>', 5, 6, 2),
            ],
            'questions' => [
                self::sq('Which planet is known as the Red Planet?', [
                    self::ta('Venus', false, 0),
                    self::ta('Jupiter', false, 1),
                    self::ta('Mars', true, 2),
                    self::ta('Saturn', false, 3),
                ], 0, true, self::img('photo-1614730321146-b6fa6a46bcb4.jpg')),
                self::sq('Who was the first human to walk on the Moon?', [
                    self::ta('Buzz Aldrin', false, 0),
                    self::ta('Yuri Gagarin', false, 1),
                    self::ta('Neil Armstrong', true, 2),
                    self::ta('Alan Shepard', false, 3),
                ], 1, true, self::img('photo-1541873676-a18131494184.jpg')),
                self::tfq('A light year is a measure of time, not distance.', false, 2, self::img('photo-1462331940025-496dfbfc7564.jpg')),
                self::sq('Which is the largest planet in our solar system?', [
                    self::ta('Saturn', false, 0),
                    self::ta('Neptune', false, 1),
                    self::ta('Jupiter', true, 2),
                    self::ta('Uranus', false, 3),
                ], 3, true, self::img('photo-1614314107768-6018061b5b72.jpg')),
                self::sq('What is the name of the first artificial satellite launched into orbit?', [
                    self::ta('Explorer 1', false, 0),
                    self::ta('Sputnik 1', true, 1),
                    self::ta('Vostok 1', false, 2),
                    self::ta('Apollo 1', false, 3),
                ], 4, true, self::img('photo-1446776811953-b23d57bd21aa.jpg')),
                self::tfq('The Sun is a star at the center of our solar system.', true, 5, self::img('photo-1692576451105-5db2a280ce38.jpg')),
            ],
        ];
    }

    private static function sports_championship(): array
    {
        return [
            'id'             => 'sports-championship',
            'title'          => 'Sports Championship Challenge',
            'description'    => 'How much do you know about world sports championships and legendary moments?',
            'type'           => 'trivia',
            'template'       => 'gamified',
            'thumbnail'      => self::img('photo-1461896836934-ffe607ba8211.jpg'),
            'question_count' => 6,
            'result_count'   => 3,
            'tags'           => ['sports', 'trivia'],
            'pro'            => true,
            'results' => [
                self::tr('r-low',  'Bench Warmer', '<p>Sport is about more than stats — the love of the game is what counts. Keep watching!</p>', 0, 2, 0),
                self::tr('r-mid',  'Team Player',  '<p>Solid sports knowledge! You\'ve clearly paid attention to the big moments.</p>', 3, 4, 1),
                self::tr('r-high', 'Champion',     '<p>Gold medal performance! Your sports knowledge is elite. The podium is yours.</p>', 5, 6, 2),
            ],
            'questions' => [
                self::sq('Which country has won the most FIFA World Cups?', [
                    self::ta('Germany', false, 0),
                    self::ta('Argentina', false, 1),
                    self::ta('Brazil', true, 2),
                    self::ta('Italy', false, 3),
                ], 0),
                self::sq('Who holds the record for most Grand Slam singles titles in tennis (men\'s)?', [
                    self::ta('Rafael Nadal', false, 0),
                    self::ta('Roger Federer', false, 1),
                    self::ta('Novak Djokovic', true, 2),
                    self::ta('Pete Sampras', false, 3),
                ], 1),
                self::tfq('The Olympics are held every 4 years.', true, 2),
                self::sq('Which NBA team has won the most championships?', [
                    self::ta('Chicago Bulls', false, 0),
                    self::ta('Golden State Warriors', false, 1),
                    self::ta('Boston Celtics', false, 2),
                    self::ta('Los Angeles Lakers', true, 3),
                ], 3),
                self::sq('Which country hosted the 2024 Summer Olympic Games?', [
                    self::ta('Japan', false, 0),
                    self::ta('France', true, 1),
                    self::ta('Australia', false, 2),
                    self::ta('Spain', false, 3),
                ], 4),
                self::tfq('Usain Bolt has won 8 Olympic gold medals.', true, 5),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — weighted (uses personality_result_id for simplicity)
    // -------------------------------------------------------------------------

    private static function marketing_maturity(): array
    {
        return [
            'id'             => 'marketing-maturity',
            'title'          => 'Marketing Maturity Assessment',
            'description'    => 'Assess your marketing maturity — strategy, content, data, channels, and automation.',
            'type'           => 'weighted',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1460925895917-afdab827c52f.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['business', 'marketing'],
            'pro'            => true,
            'results' => [
                self::pr('r-beginner',     'Marketing Explorer',     '<p>You\'re building the foundation. Focus on nailing your core message and one consistent channel before scaling.</p>', 0),
                self::pr('r-intermediate', 'Marketing Practitioner', '<p>You have solid fundamentals in place. The next step is deeper measurement and multi-channel coordination.</p>', 1),
                self::pr('r-advanced',     'Marketing Leader',       '<p>Your marketing operation is sophisticated and data-driven. You\'re setting the benchmark others follow.</p>', 2),
            ],
            'questions' => [
                self::sq('How documented is your marketing strategy?', [
                    self::pa('We have a detailed, written strategy reviewed quarterly', 'r-advanced', 0),
                    self::pa('We have a rough plan but it\'s not well documented', 'r-intermediate', 1),
                    self::pa('We wing it based on what feels right', 'r-beginner', 2),
                ], 0),
                self::sq('How do you measure marketing effectiveness?', [
                    self::pa('Full attribution, dashboards, and regular reporting', 'r-advanced', 0),
                    self::pa('Some tracking — we watch key metrics', 'r-intermediate', 1),
                    self::pa('We mostly guess or rely on gut feel', 'r-beginner', 2),
                ], 1),
                self::sq('How is your content marketing operation?', [
                    self::pa('Consistent, SEO-driven, and tied to revenue', 'r-advanced', 0),
                    self::pa('Regular publishing but not always strategic', 'r-intermediate', 1),
                    self::pa('Ad-hoc — when we have time', 'r-beginner', 2),
                ], 2),
                self::sq('How many marketing channels do you actively manage?', [
                    self::pa('4 or more, coordinated as a system', 'r-advanced', 0),
                    self::pa('2–3 channels with moderate consistency', 'r-intermediate', 1),
                    self::pa('1 or we jump between them randomly', 'r-beginner', 2),
                ], 3),
                self::sq('What level of marketing automation do you have?', [
                    self::pa('Full automation: CRM, email flows, lead scoring', 'r-advanced', 0),
                    self::pa('Basic automation — email sequences and some workflows', 'r-intermediate', 1),
                    self::pa('Little to none — mostly manual', 'r-beginner', 2),
                ], 4),
            ],
        ];
    }

    private static function leadership_style(): array
    {
        return [
            'id'             => 'leadership-style',
            'title'          => 'Leadership Style Inventory',
            'description'    => 'Identify your leadership style — visionary, servant leader, or democratic.',
            'type'           => 'weighted',
            'template'       => 'splitscreen',
            'thumbnail'      => self::img('photo-1519389950473-47ba0277781c.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['business', 'leadership'],
            'pro'            => true,
            'results' => [
                self::pr('r-visionary',  'The Visionary',        '<p>You lead with a compelling picture of the future. People follow you because they believe in where you\'re going — and trust you to get them there.</p>', 0),
                self::pr('r-servant',    'The Servant Leader',   '<p>You put your team first. By removing obstacles and developing others, you create the conditions for exceptional performance.</p>', 1),
                self::pr('r-democratic', 'The Democratic Leader','<p>You build consensus and draw out the best collective thinking. Your team feels ownership over decisions — and delivers accordingly.</p>', 2),
            ],
            'questions' => [
                self::sq('How do you make important decisions?', [
                    self::pa('I cast a clear vision and decide based on long-term direction', 'r-visionary', 0),
                    self::pa('I ask what decision best serves my team and stakeholders', 'r-servant', 1),
                    self::pa('I gather input from everyone involved before deciding', 'r-democratic', 2),
                ], 0, true, self::img('photo-1519389950473-47ba0277781c.jpg')),
                self::sq('How do you motivate your team?', [
                    self::pa('By connecting their work to a larger mission and purpose', 'r-visionary', 0),
                    self::pa('By genuinely caring about their growth and wellbeing', 'r-servant', 1),
                    self::pa('By involving them in goal-setting and giving them ownership', 'r-democratic', 2),
                ], 1, true, self::img('photo-1522071820081-009f0129c71c.jpg')),
                self::sq('How do you handle conflict within your team?', [
                    self::pa('Refocus everyone on the shared goal and direction', 'r-visionary', 0),
                    self::pa('Listen carefully to all sides and address root causes', 'r-servant', 1),
                    self::pa('Facilitate an open discussion to reach common ground', 'r-democratic', 2),
                ], 2, true, self::img('photo-1573497019236-17f8177b81e8.jpg')),
                self::sq('What does success look like to you?', [
                    self::pa('The team achieves something nobody thought was possible', 'r-visionary', 0),
                    self::pa('Each person on my team is growing and thriving', 'r-servant', 1),
                    self::pa('The team feels ownership and pride in what we built together', 'r-democratic', 2),
                ], 3, true, self::img('photo-1552664730-d307ca884978.jpg')),
                self::sq('How do you prefer to communicate?', [
                    self::pa('Inspirational — I want to energize and rally people', 'r-visionary', 0),
                    self::pa('Empathetic — I listen more than I speak', 'r-servant', 1),
                    self::pa('Transparent — I share all relevant context and invite feedback', 'r-democratic', 2),
                ], 4, true, self::img('photo-1600880292203-757bb62b4baf.jpg')),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — branching (uses personality_result_id for structure)
    // -------------------------------------------------------------------------

    private static function product_finder(): array
    {
        return [
            'id'             => 'product-finder',
            'title'          => 'Product Recommendation Finder',
            'description'    => 'Guide customers to the right product tier through smart qualifying questions.',
            'type'           => 'branching',
            'template'       => 'classic',
            'thumbnail'      => self::img('photo-1557804506-669a67965ba0.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['business', 'sales'],
            'pro'            => true,
            'results' => [
                self::pr('r-starter',    'Starter Plan',    '<p>The Starter plan is perfect for you! It has everything you need to get up and running quickly at the right price point.</p>', 0),
                self::pr('r-pro',        'Pro Plan',        '<p>You\'re a great fit for the Pro plan! It unlocks the advanced features and scale that your use case needs.</p>', 1),
                self::pr('r-enterprise', 'Enterprise Plan', '<p>You need the Enterprise plan. Your requirements demand dedicated support, advanced compliance, and custom integrations.</p>', 2),
            ],
            'questions' => [
                self::sq("What's the size of your team?", [
                    self::pa('Just me or a small team of 1–5', 'r-starter', 0),
                    self::pa('A growing team of 6–50 people', 'r-pro', 1),
                    self::pa('A large organization of 50+ people', 'r-enterprise', 2),
                ], 0),
                self::sq("What's your primary use case?", [
                    self::pa('Getting started and exploring the product', 'r-starter', 0),
                    self::pa('Running real campaigns or production workflows', 'r-pro', 1),
                    self::pa('Enterprise-wide deployment with compliance needs', 'r-enterprise', 2),
                ], 1),
                self::sq('What best describes your budget?', [
                    self::pa('I\'m looking for a free or low-cost option', 'r-starter', 0),
                    self::pa('I have a reasonable budget for a proven tool', 'r-pro', 1),
                    self::pa('Budget is secondary to capability and support', 'r-enterprise', 2),
                ], 2),
                self::sq('Do you need dedicated customer support?', [
                    self::pa('Self-serve documentation is fine', 'r-starter', 0),
                    self::pa('Priority email or chat support would be helpful', 'r-pro', 1),
                    self::pa('A dedicated account manager is essential', 'r-enterprise', 2),
                ], 3),
                self::sq('Do you need advanced security or SSO?', [
                    self::pa('Not at this stage', 'r-starter', 0),
                    self::pa('Would be nice but not blocking', 'r-pro', 1),
                    self::pa('Required — it\'s non-negotiable', 'r-enterprise', 2),
                ], 4),
            ],
        ];
    }

    private static function it_support_triage(): array
    {
        return [
            'id'             => 'it-support-triage',
            'title'          => 'IT Support Triage',
            'description'    => 'Route IT support tickets intelligently based on device, issue type, and urgency.',
            'type'           => 'branching',
            'template'       => 'conversational',
            'thumbnail'      => self::img('photo-1518770660439-4636190af475.jpg'),
            'question_count' => 5,
            'result_count'   => 3,
            'tags'           => ['tech', 'business'],
            'pro'            => true,
            'results' => [
                self::pr('r-self-help', 'Self-Help Resources',  '<p>We found relevant help articles for your issue! Check the linked resources — most users resolve this in under 5 minutes.</p>', 0),
                self::pr('r-ticket',    'Open a Support Ticket','<p>We\'ve pre-filled a support ticket with your details. A technician will respond within 4 business hours.</p>', 1),
                self::pr('r-urgent',    'Escalate to L2 Support','<p>Your issue needs immediate attention. L2 support has been notified and will contact you within 30 minutes.</p>', 2),
            ],
            'questions' => [
                self::sq('What type of device are you having trouble with?', [
                    self::pa('Laptop or desktop (Windows)', 'r-ticket', 0),
                    self::pa('Laptop or desktop (Mac)', 'r-ticket', 1),
                    self::pa('Mobile device (iOS or Android)', 'r-self-help', 2),
                    self::pa('A server or network device', 'r-urgent', 3),
                ], 0),
                self::sq('What category does your issue fall under?', [
                    self::pa('Software / app not working', 'r-self-help', 0),
                    self::pa('Account access or login problem', 'r-ticket', 1),
                    self::pa('Hardware or peripheral issue', 'r-ticket', 2),
                    self::pa('Network outage or security incident', 'r-urgent', 3),
                ], 1),
                self::sq('How severe is the impact?', [
                    self::pa('Minor inconvenience — I can still work', 'r-self-help', 0),
                    self::pa('I\'m partially blocked but have workarounds', 'r-ticket', 1),
                    self::pa('I\'m completely blocked and can\'t work', 'r-urgent', 2),
                    self::pa('Multiple people or a whole team are affected', 'r-urgent', 3),
                ], 2),
                self::sq('Have you tried restarting the device or app?', [
                    self::pa('Yes — didn\'t help', 'r-ticket', 0),
                    self::pa('No — let me try that first', 'r-self-help', 1),
                    self::pa('This isn\'t a restart-fixable issue', 'r-urgent', 2),
                ], 3),
                self::sq('How long has this been happening?', [
                    self::pa('Just started — less than an hour', 'r-self-help', 0),
                    self::pa('A few hours or since this morning', 'r-ticket', 1),
                    self::pa('More than a day', 'r-ticket', 2),
                    self::pa('It\'s escalating right now — critical', 'r-urgent', 3),
                ], 4),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — survey
    // -------------------------------------------------------------------------

    private static function employee_wellbeing(): array
    {
        return [
            'id'             => 'employee-wellbeing',
            'title'          => 'Employee Wellbeing Check',
            'description'    => 'A confidential wellbeing check covering workload, support, and engagement.',
            'type'           => 'survey',
            'template'       => 'conversational',
            'thumbnail'      => self::img('photo-1552581234-26160f608093.jpg'),
            'question_count' => 5,
            'result_count'   => 1,
            'tags'           => ['business', 'hr'],
            'pro'            => false, // survey (rating questions) on Conversational: all free
            'results' => [self::sr('Thank you for sharing.', '<p>Your responses are confidential and will help us build a better workplace. Thank you for your trust and honesty.</p>')],
            'questions' => [
                self::rq('How would you rate your overall wellbeing at work this week?', 0),
                self::sq('How manageable is your current workload?', [
                    self::sa('Very manageable — I have room to breathe', 0),
                    self::sa('Manageable but busy', 1),
                    self::sa('Heavy — I\'m stretched thin', 2),
                    self::sa('Overwhelming right now', 3),
                ], 1),
                self::sq('Do you feel supported by your manager?', [
                    self::sa('Yes — very well supported', 0),
                    self::sa('Generally yes', 1),
                    self::sa('Somewhat — could be better', 2),
                    self::sa('I don\'t feel supported right now', 3),
                ], 2),
                self::rq('How engaged do you feel with your work right now?', 3),
                self::tq('Is there anything specific we could do to better support you? (optional)', 4, false),
            ],
        ];
    }

    private static function content_creator_intake(): array
    {
        return [
            'id'             => 'content-creator-intake',
            'title'          => 'Content Creator Intake',
            'description'    => 'Qualify new content creator partnerships with this structured intake form.',
            'type'           => 'survey',
            'template'       => 'magazine',
            'thumbnail'      => self::img('photo-1542744173-8e7e53415bb0.jpg'),
            'question_count' => 5,
            'result_count'   => 1,
            'tags'           => ['marketing', 'creative'],
            'pro'            => true,
            'results' => [self::sr("We'll review your submission and follow up!", '<p>Thank you for applying! Our partnerships team reviews all submissions within 5 business days. We\'ll reach out by email.</p>')],
            'questions' => [
                self::sq('What type of content do you primarily create?', [
                    self::sa('Short-form video (TikTok, Reels)', 0),
                    self::sa('Long-form video (YouTube)', 1),
                    self::sa('Written content (blog, newsletter)', 2),
                    self::sa('Photography or visual content', 3),
                    self::sa('Podcast or audio content', 4),
                ], 0),
                self::sq('What is your combined audience size across platforms?', [
                    self::sa('Under 10,000 followers', 0),
                    self::sa('10,000–100,000 followers', 1),
                    self::sa('100,000–1 million followers', 2),
                    self::sa('Over 1 million followers', 3),
                ], 1),
                self::mq('Which niches best describe your content? (Select all that apply)', [
                    self::sa('Lifestyle', 0),
                    self::sa('Tech & Gadgets', 1),
                    self::sa('Health & Fitness', 2),
                    self::sa('Business & Finance', 3),
                    self::sa('Education', 4),
                ], 2),
                self::sq("What is your primary goal from this partnership?", [
                    self::sa('Monetize my audience', 0),
                    self::sa('Grow my following', 1),
                    self::sa('Access exclusive products or tools', 2),
                    self::sa('Build long-term brand relationships', 3),
                ], 3),
                self::tq('Share a link to your best recent piece of content.', 4, true),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRO PRESETS — poll
    // -------------------------------------------------------------------------

    private static function dev_tools_poll(): array
    {
        return [
            'id'             => 'dev-tools-poll',
            'title'          => 'Best Dev Tool Showdown',
            'description'    => 'Which development tool or IDE is your daily driver? Vote and see the results!',
            'type'           => 'poll',
            'template'       => 'gamified',
            'thumbnail'      => self::img('photo-1461749280684-dccba630e2f6.jpg'),
            'question_count' => 1,
            'result_count'   => 1,
            'tags'           => ['tech', 'dev'],
            'pro'            => true,
            'results' => [self::sr('Thanks for voting!', '<p>The dev community has spoken! Share this with your team and see how you compare.</p>')],
            'questions' => [
                self::sq('Which IDE or code editor is your daily driver?', [
                    self::sa('VS Code', 0),
                    self::sa('JetBrains IDEs (WebStorm, PHPStorm, etc.)', 1),
                    self::sa('Neovim / Vim', 2),
                    self::sa('Cursor', 3),
                    self::sa('Sublime Text', 4),
                ], 0),
            ],
        ];
    }
}
