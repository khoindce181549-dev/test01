import { registerBlockType } from '@wordpress/blocks';
import {
  useBlockProps,
  InspectorControls,
} from '@wordpress/block-editor';
import {
  PanelBody,
  SelectControl,
  ToggleControl,
  Spinner,
} from '@wordpress/components';
import { useEffect, useState, createElement as h } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

registerBlockType('quizably/quiz', {
  title: __('Advance Quiz', 'quizably'),
  icon: 'editor-help',
  category: 'widgets',
  attributes: {
    quizId: { type: 'string', default: '' },
    autoStart: { type: 'boolean', default: false },
  },
  edit({ attributes, setAttributes }) {
    const props = useBlockProps({ className: 'quizably-block-edit' });
    const [quizzes, setQuizzes] = useState(null);

    useEffect(() => {
      apiFetch({
        path: '/quizably/v1/quizzes?limit=100&orderby=updated_at&order=DESC',
      })
        .then((resp) => setQuizzes(resp.items ?? []))
        .catch(() => setQuizzes([]));
    }, []);

    const options = quizzes
      ? [
          {
            value: '',
            label: __('— Select a quiz —', 'quizably'),
          },
          ...quizzes.map((q) => ({
            value: String(q.id),
            label: q.title,
          })),
        ]
      : [];

    // Use createElement rather than JSX so the bundle stays plain JS and
    // does not require a Vue/React toolchain in Vite. @wordpress/element
    // re-exports React's createElement as the canonical factory.
    return h(
      'div',
      props,
      h(
        InspectorControls,
        null,
        h(
          PanelBody,
          { title: __('Quiz', 'quizably') },
          quizzes === null
            ? h(Spinner, null)
            : h(SelectControl, {
                label: __('Choose quiz', 'quizably'),
                value: attributes.quizId,
                options,
                onChange: (v) => setAttributes({ quizId: v }),
              }),
          h(ToggleControl, {
            label: __('Auto-start on load', 'quizably'),
            help: __(
              'Skip the intro screen and go straight to the first question.',
              'quizably'
            ),
            checked: attributes.autoStart,
            onChange: (v) => setAttributes({ autoStart: v }),
          })
        )
      ),
      h(
        'div',
        { className: 'quizably-block-preview' },
        attributes.quizId
          ? h(
              'p',
              null,
              __(
                'Advance Quiz (rendered on the frontend):',
                'quizably'
              ) +
                ' ' +
                attributes.quizId
            )
          : h(
              'p',
              null,
              __(
                'Choose a quiz in the block settings.',
                'quizably'
              )
            )
      )
    );
  },
  // Server-rendered — save returns null.
  save: () => null,
});
