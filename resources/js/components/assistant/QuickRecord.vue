<template>
  <div class="assistant-card">
    <div v-if="!enabled" class="assistant-empty">
      <h2>{{ $t('assistant.quick_record_disabled_title') }}</h2>
      <p>{{ $t('assistant.quick_record_disabled_body') }}</p>
    </div>

    <template v-else>
      <div class="assistant-card-header">
        <div>
          <p class="assistant-eyebrow">
            BondMemo Agent
          </p>
          <h1>{{ $t('assistant.quick_record_title') }}</h1>
          <p>{{ $t('assistant.quick_record_intro') }}</p>
        </div>
        <span class="assistant-step">{{ step === 'compose' ? '1 / 2' : '2 / 2' }}</span>
      </div>

      <div v-if="step === 'compose'">
        <label class="b db mb2">{{ $t('assistant.contacts') }}</label>
        <contact-autosuggest
          id="assistant-contact-search"
          :placeholder="$t('assistant.contact_placeholder')"
          :component-item="componentItem"
          :filter="filterContact"
          :add-no-result="false"
          :overflow="true"
          @select="selectContact"
        />

        <div class="assistant-contact-chips">
          <button
            v-for="contact in contacts"
            :key="contact.id"
            type="button"
            class="assistant-chip"
            @click="removeContact(contact)"
          >
            {{ contact.complete_name }} <span aria-hidden="true">×</span>
          </button>
        </div>

        <label for="assistant-date" class="b db mb2">{{ $t('assistant.interaction_date') }}</label>
        <input id="assistant-date" v-model="happenedAt" class="form-control assistant-date" type="date" />

        <label for="assistant-original-text" class="b db mb2 mt3">{{ $t('assistant.original_text') }}</label>
        <textarea
          id="assistant-original-text"
          v-model="text"
          class="form-control"
          rows="8"
          :placeholder="$t('assistant.original_text_placeholder')"
        ></textarea>

        <p class="assistant-privacy-note">
          {{ $t('assistant.third_party_notice') }}
        </p>
        <p v-if="error" class="assistant-error">
          {{ error }}
        </p>

        <button class="btn btn-primary" :disabled="loading || !canAnalyze" @click="analyze">
          {{ loading ? $t('assistant.analyzing') : $t('assistant.analyze') }}
        </button>
      </div>

      <div v-else-if="step === 'review'">
        <div class="assistant-review-section">
          <label for="assistant-summary" class="b db mb2">{{ $t('assistant.summary') }}</label>
          <input id="assistant-summary" v-model="proposal.summary" class="form-control" maxlength="255" />
        </div>

        <div class="assistant-review-section">
          <h3>{{ $t('assistant.original_text') }}</h3>
          <textarea v-model="text" class="form-control" rows="5"></textarea>
        </div>

        <div class="assistant-review-section">
          <h3>{{ $t('assistant.tasks') }}</h3>
          <p v-if="proposal.tasks.length === 0" class="assistant-muted">
            {{ $t('assistant.none_found') }}
          </p>
          <div v-for="(task, index) in proposal.tasks" :key="'task-'+index" class="assistant-proposal-row">
            <input v-model="task.selected" type="checkbox" :aria-label="$t('assistant.include_item')" />
            <input v-model="task.title" class="form-control" maxlength="255" />
            <select v-model="task.contact_id" class="form-control">
              <option :value="null">
                {{ $t('assistant.no_contact') }}
              </option>
              <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                {{ contact.complete_name }}
              </option>
            </select>
          </div>
        </div>

        <div class="assistant-review-section">
          <h3>{{ $t('assistant.reminders') }}</h3>
          <p v-if="proposal.reminders.length === 0" class="assistant-muted">
            {{ $t('assistant.none_found') }}
          </p>
          <div v-for="(reminder, index) in proposal.reminders" :key="'reminder-'+index" class="assistant-proposal-row assistant-proposal-row-reminder">
            <input v-model="reminder.selected" type="checkbox" :aria-label="$t('assistant.include_item')" />
            <input v-model="reminder.title" class="form-control" />
            <input v-model="reminder.date" class="form-control" type="date" />
            <select v-model="reminder.contact_id" class="form-control">
              <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                {{ contact.complete_name }}
              </option>
            </select>
          </div>
        </div>

        <p class="assistant-confirm-note">
          {{ $t('assistant.confirm_note') }}
        </p>
        <p v-if="error" class="assistant-error">
          {{ error }}
        </p>
        <div class="assistant-actions">
          <button class="btn" :disabled="saving" @click="step = 'compose'">
            {{ $t('app.back') }}
          </button>
          <button class="btn btn-primary" :disabled="saving || !proposal.summary" @click="save">
            {{ saving ? $t('assistant.saving') : $t('assistant.confirm_and_save') }}
          </button>
        </div>
      </div>

      <div v-else class="assistant-success">
        <div class="assistant-success-mark">
          ✓
        </div>
        <h2>{{ $t('assistant.saved_title') }}</h2>
        <p>{{ $t('assistant.saved_body') }}</p>
        <button class="btn btn-primary" @click="reset">
          {{ $t('assistant.record_another') }}
        </button>
      </div>
    </template>
  </div>
</template>

<script>
import moment from 'moment';
import ContactAutosuggest from '../people/partials/ContactAutosuggest.vue';
import ContactMultiItem from '../people/partials/ContactMultiItem.vue';

export default {
  components: { ContactAutosuggest },
  props: {
    enabled: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      step: 'compose',
      contacts: [],
      text: '',
      happenedAt: moment().format('YYYY-MM-DD'),
      proposal: { summary: '', tasks: [], reminders: [] },
      loading: false,
      saving: false,
      error: '',
    };
  },
  computed: {
    componentItem() {
      return ContactMultiItem;
    },
    canAnalyze() {
      return this.contacts.length > 0 && this.text.trim().length > 0;
    },
  },
  methods: {
    filterContact(contact) {
      return contact.id > 0 && !this.contacts.some(item => item.id === contact.id);
    },
    selectContact(selection) {
      if (selection.item && selection.item.id > 0 && this.filterContact(selection.item)) {
        this.contacts.push(selection.item);
      }
    },
    removeContact(contact) {
      this.contacts = this.contacts.filter(item => item.id !== contact.id);
    },
    analyze() {
      this.loading = true;
      this.error = '';
      axios.post('assistant/quick-record/analyze', {
        contact_ids: this.contacts.map(contact => contact.id),
        text: this.text,
        happened_at: this.happenedAt,
      }).then(response => {
        this.proposal = response.data.data;
        this.proposal.tasks = this.proposal.tasks.map(item => Object.assign({}, item, { selected: true }));
        this.proposal.reminders = this.proposal.reminders.map(item => Object.assign({}, item, { selected: true }));
        this.step = 'review';
      }).catch(error => {
        this.error = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : this.$t('app.error_try_again');
      }).finally(() => {
        this.loading = false;
      });
    },
    save() {
      this.saving = true;
      this.error = '';
      axios.post('assistant/quick-record', {
        contact_ids: this.contacts.map(contact => contact.id),
        text: this.text,
        happened_at: this.happenedAt,
        summary: this.proposal.summary,
        tasks: this.proposal.tasks.filter(item => item.selected).map(item => this.withoutSelected(item)),
        reminders: this.proposal.reminders.filter(item => item.selected).map(item => this.withoutSelected(item)),
      }).then(() => {
        this.step = 'saved';
      }).catch(error => {
        this.error = error.response && error.response.data && error.response.data.message
          ? error.response.data.message
          : this.$t('app.error_try_again');
      }).finally(() => {
        this.saving = false;
      });
    },
    reset() {
      this.step = 'compose';
      this.contacts = [];
      this.text = '';
      this.happenedAt = moment().format('YYYY-MM-DD');
      this.proposal = { summary: '', tasks: [], reminders: [] };
      this.error = '';
    },
    withoutSelected(item) {
      const result = Object.assign({}, item);
      delete result.selected;
      return result;
    },
  },
};
</script>
