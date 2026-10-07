<template>
  <component
    :is="component"
    :colors="colors"
    :button-style="buttonStyle"
    :background-image="backgroundImage"
    :overlay-color="overlayColor"
    :overlay-opacity="overlayOpacity"
    :question="question"
    :answers="answers"
    :position="position"
    :total="total"
    :font-family="fontFamily"
    :font-size-pct="fontSizePct"
    :split-layout="splitLayout"
    :split-image-width="splitImageWidth"
    :split-content-align="splitContentAlign"
    :split-height-value="splitHeightValue"
    :split-height-unit="splitHeightUnit"
    :split-width-value="splitWidthValue"
    :split-width-unit="splitWidthUnit"
  />
</template>

<script setup>
import { computed } from 'vue';
import ClassicPreview from './ClassicPreview.vue';
import MinimalPreview from './MinimalPreview.vue';
import FullscreenPreview from './FullscreenPreview.vue';
import SplitscreenPreview from './SplitscreenPreview.vue';
import CardstackPreview from './CardstackPreview.vue';
import ConversationalPreview from './ConversationalPreview.vue';
import GamifiedPreview from './GamifiedPreview.vue';
import MagazinePreview from './MagazinePreview.vue';

const props = defineProps({
  templateSlug: { type: String, default: 'classic' },
  colors: { type: Object, default: () => ({}) },
  buttonStyle: { type: String, default: 'rounded' },
  backgroundImage: { type: String, default: '' },
  overlayColor: { type: String, default: '#FFFFFF' },
  overlayOpacity: { type: Number, default: 88 },
  fontFamily: { type: String, default: 'default' },
  fontSizePct: { type: Number, default: 100 },
  splitLayout: { type: String, default: 'image-left' },
  splitImageWidth:   { type: Number, default: 42 },
  splitContentAlign: { type: String, default: 'center' },
  splitHeightValue:  { type: Number, default: 100 },
  splitHeightUnit:   { type: String, default: 'vh' },
  splitWidthValue:   { type: Number, default: 100 },
  splitWidthUnit:    { type: String, default: 'vw' },
  question: { type: String, default: 'What flavor matches your mood today?' },
  answers: {
    type: Array,
    default: () => ['Bright and citrusy', 'Warm and spiced', 'Cool and minty'],
  },
  position: { type: Number, default: 1 },
  total: { type: Number, default: 5 },
});

const REGISTRY = {
  classic: ClassicPreview,
  minimal: MinimalPreview,
  fullscreen: FullscreenPreview,
  splitscreen: SplitscreenPreview,
  cardstack: CardstackPreview,
  conversational: ConversationalPreview,
  gamified: GamifiedPreview,
  magazine: MagazinePreview,
};

const component = computed(() => REGISTRY[props.templateSlug] || ClassicPreview);
</script>
