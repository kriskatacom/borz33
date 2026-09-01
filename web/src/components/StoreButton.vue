<script setup>
defineProps({ busy: Boolean, disabled: Boolean, type: { type: String, default: 'button' }, label: { type: String, required: true }, busyLabel: { type: String, default: 'Изчакайте…' } });
</script>
<template><button :type="type" class="store-button" :disabled="disabled || busy" :aria-busy="busy ? 'true' : 'false'"><span v-if="busy" class="store-button-spinner" aria-hidden="true"></span><span>{{ busy ? busyLabel : label }}</span></button></template>
