<template>
  <div class="contact-brief">
    <button v-if="!brief" class="btn btn-primary" :disabled="loading || !enabled" @click="generate">
      {{ loading ? $t('assistant.generating_brief') : $t('assistant.generate_brief') }}
    </button>
    <span v-if="!enabled" class="assistant-muted ml2">{{ $t('assistant.agent_disabled_short') }}</span>
    <p v-if="error" class="assistant-error mt2">
      {{ error }}
    </p>

    <div v-if="brief" class="contact-brief-panel">
      <div class="contact-brief-heading">
        <div>
          <p class="assistant-eyebrow">
            {{ $t('assistant.pre_contact_brief') }}
          </p>
          <h2>{{ contactName }}</h2>
        </div>
        <button class="btn" :disabled="loading" @click="generate">
          {{ $t('assistant.refresh') }}
        </button>
      </div>
      <div v-if="brief.overview.sources.length > 0" class="contact-brief-overview">
        <p>{{ brief.overview.text }}</p>
        <small>{{ brief.overview.sources.map(source => source.label).join(' · ') }}</small>
      </div>
      <div class="contact-brief-grid">
        <section v-for="section in sections" :key="section.key" class="contact-brief-section">
          <h3>{{ $t(section.title) }}</h3>
          <p v-if="brief[section.key].length === 0" class="assistant-muted">
            {{ $t('assistant.no_brief_items') }}
          </p>
          <ul v-else>
            <li v-for="(item, index) in brief[section.key]" :key="section.key+'-'+index">
              <span>{{ item.text }}</span>
              <small>{{ item.sources.map(source => source.label).join(' · ') }}</small>
            </li>
          </ul>
        </section>
      </div>
      <p class="assistant-privacy-note">
        {{ $t('assistant.brief_source_notice') }}
      </p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    contactId: { type: Number, required: true },
    contactName: { type: String, required: true },
    enabled: { type: Boolean, default: false },
  },
  data() {
    return {
      brief: null,
      loading: false,
      error: '',
      sections: [
        { key: 'recent_events', title: 'assistant.brief_recent_events' },
        { key: 'commitments', title: 'assistant.brief_commitments' },
        { key: 'upcoming', title: 'assistant.brief_upcoming' },
        { key: 'relationship_context', title: 'assistant.brief_relationships' },
        { key: 'conversation_starters', title: 'assistant.brief_conversation_starters' },
      ],
    };
  },
  methods: {
    generate() {
      this.loading = true;
      this.error = '';
      axios.post(`assistant/people/${this.contactId}/brief`)
        .then(response => { this.brief = response.data.data; })
        .catch(error => {
          this.error = error.response && error.response.data && error.response.data.message
            ? error.response.data.message
            : this.$t('app.error_try_again');
        })
        .finally(() => { this.loading = false; });
    },
  },
};
</script>
