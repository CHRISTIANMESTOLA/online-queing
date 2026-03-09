<template>
  <q-card class="monitor-card full-height">
    <q-card-section class="row items-center justify-between q-pb-none">
      <div>
        <div class="text-subtitle1 text-weight-bold">{{ office.name }}</div>
        <div class="text-caption text-grey-7">Office Prefix: {{ office.prefix }}</div>
      </div>
      <q-badge color="primary" class="text-weight-bold">{{ office.prefix }}</q-badge>
    </q-card-section>

    <q-card-section class="q-pt-md">
      <div class="text-caption text-grey-7">Now Serving</div>
      <div class="text-h4 text-weight-bold text-primary q-mb-md">
        {{ office.now_serving || '---' }}
      </div>

      <div class="row q-col-gutter-sm q-mb-sm">
        <div class="col-6">
          <div class="text-caption text-grey-7">Next In Line</div>
          <div class="text-subtitle2 text-weight-medium">
            {{ office.next_in_line || '---' }}
          </div>
        </div>
        <div class="col-6">
          <div class="text-caption text-grey-7">Waiting</div>
          <div class="text-subtitle2 text-weight-medium">{{ office.waiting_count }}</div>
        </div>
      </div>

      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <div class="text-caption text-grey-7">Done</div>
          <div class="text-subtitle2 text-weight-medium">{{ office.done_count }}</div>
        </div>
        <div class="col-6">
          <div class="text-caption text-grey-7">Skipped</div>
          <div class="text-subtitle2 text-weight-medium">{{ office.skipped_count }}</div>
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
defineProps({
  office: {
    type: Object,
    required: true,
  },
})
</script>
