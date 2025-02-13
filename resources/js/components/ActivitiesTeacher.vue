<template>
  <div>
    <portal to="navbar">
      <!-- Select roster -->
      <b-nav-item-dropdown :text="current_roster != null ? current_roster.name : 'Toutes les classes'">
        <b-dropdown-item @click="rosterChange(null)" :active="current_roster == null">
          Toutes les classes
        </b-dropdown-item>
        <div class="dropdown-divider"></div>
        <b-dropdown-item
          @click="rosterChange(roster)"
          v-for="roster in rosters"
          v-model="current_roster"
          :key="roster.id"
          :active="current_roster != null && roster.id == current_roster.id"
        >
          <b-icon-people />
          {{ roster.name }}
          <b-spinner v-if="roster.has_running_activities" type="grow" small></b-spinner>
        </b-dropdown-item>
      </b-nav-item-dropdown>
    </portal>

    <div class="mt-4 container">
      <h2>Activités de {{ current_roster != null ? current_roster.name : 'toutes les classes' }}</h2>
      <p>
        Les activités en cours et passées sont accessibles des étudiants concernés. En cachant une activité, les
        étudiants ne pourront plus y accéder.
      </p>


      <b-table striped hover :items="activities" :fields="fields">
        <template v-slot:cell(updated_at)="data">
          <time-toggle :value="data.item.updated_at"></time-toggle>
        </template>
        <template v-slot:cell(actions)="data">
          <!-- Show the activity -->
          <b-button
            v-if="data.item.status == 'finished' && data.item.hidden"
            @click="$activity.show(data.item.id)"
            variant="outline-primary"
            class="btn-circle"
            v-b-popover.hover.top="'Rendre visible l\'activité'"
          >
            <b-icon-eye-slash />
          </b-button>

          <!-- Hide the activity -->
          <b-button
            v-if="data.item.status == 'finished' && !data.item.hidden"
            @click="$activity.hide(data.item.id)"
            variant="outline-secondary"
            class="btn-circle"
            v-b-popover.hover.top="'Cacher l\'activité aux étudiants'"
          >
            <b-icon-eye-fill />
          </b-button>

          <!-- View results -->
          <b-button
            v-if="data.item.status == 'finished'"
            :to="`/activities/${data.item.id}/results`"
            variant="outline-primary"
            class="btn-circle"
            v-b-popover.hover.top="'Voir les résultats'"
          >
            <b-icon-trophy-fill />
          </b-button>

          <!-- View student list -->
          <b-button
            v-if="data.item.status == 'finished'"
            :to="`/activities/${data.item.id}/studentList`"
            variant="outline-primary"
            class="btn-circle"
            v-b-popover.hover.top="'Liste des étudiants'"
          >
            <b-icon-person-lines-fill />
          </b-button>
          

          <!-- Delete an activity -->
          <b-button
            v-if="data.item.status == 'idle'"
            v-b-popover.hover.top="'Supprimer l\'activité'"
            variant="outline-danger"
            class="btn-circle"
            @click="$activity.delete(data.item.id)"
          >
            <b-icon-trash />
          </b-button>

          <!-- Open an activity -->
          <b-button
            v-if="data.item.status == 'idle'"
            @click="$activity.open(data.item.id)"
            variant="outline-success"
            class="btn-circle"
            v-b-popover.hover.top="'Ouvrir l\'activité pour participation'"
          >
            <b-icon-broadcast />
          </b-button>

          <!-- Start the activity -->
          <b-button
            v-if="data.item.status == 'opened'"
            @click="$activity.start(data.item.id)"
            variant="outline-success"
            class="btn-circle"
            v-b-popover.hover.top="'Démarrer l\'activité'"
          >
            <b-icon-play-fill />
          </b-button>

          <!-- Open an activity -->
          <b-button
            v-if="data.item.status == 'opened'"
            @click="$activity.close(data.item.id)"
            variant="success"
            class="btn-circle running"
            v-b-popover.hover.top="'Activité disponible pour les étudiants. Cliquez pour interrompre.'"
          >
            <b-icon-broadcast />
          </b-button>

          <!-- Follow an activity -->
          <b-button
            v-if="data.item.status == 'running' || data.item.status == 'finished'"
            variant="outline-info"
            class="btn-circle"
            :to="`/activities/${data.item.id}/progression`"
            v-b-popover.hover.top="'Voir la progression en temps réel.'"
          >
            <b-icon-bar-chart-fill />
          </b-button>
          <!--
          <span v-if="data.item.status == 'opened'">
            {{ activities }} / {{ data.item.roster.students }}
          </span>
          -->

          <countdown
            @end="data.item.status = 'finished'"
            v-if="data.item.status == 'running'"
            :time="data.item.duration * 1000 - (Date.now() - Date.parse(data.item.started_at)) + 2000"
            
          >
            <template slot-scope="props">
              <span :class="{ 'text-danger': props.totalMilliseconds <= 30 * 1000 }">
                {{ String(props.hours)}}
                :
                {{ String(props.minutes).padStart(2, '0') }}
                :
                {{ String(props.seconds).padStart(2, '0') }}
              </span>
            </template>
          </countdown>
        </template>
      </b-table>
    </div>
  </div>
</template>

<script>
import VueCountdown from '@chenfengyuan/vue-countdown';
import TimeToggle from '../layouts/time-toggle'

export default {
  components: {
    countdown: VueCountdown,
    TimeToggle
  },
  data() {
    return {
      activity: {
        quiz_id: null,
        roster_id: null,
        duration: 600
      },
      fields: [
        {
          key: 'quiz.name',
          label: 'Quiz',
          sortable: true
        },
        {
          key: 'quiz.questions',
          label: 'Questions',
          sortable: true
        },
        {
          key: 'roster.name',
          label: 'Classe',
          sortable: true,
          class: 'd-none d-md-table-cell'
        },
        {
          key: 'duration',
          label: 'Durée',
          sortable: true,
          formatter: 'humanDuration'
        },
        {
          key: 'updated_at',
          label: 'Modifié',
          sortable: true
        },
        {
          label: 'Actions',
          key: 'actions'
        }
      ],
    };
  },
  watch: {
    current_roster(roster) {
      let id = roster != null ? roster.id : null;

      // Hide roster column if one is selected
      let field = this.activities.fields.find(field => field.key == 'roster.name');
      field.class = id ? 'd-none' : '';

      this.loadActivities(id);
    }
  },
  computed: {
    activities() {
      return this.$store.state.activity.data;
    },
    rosters() {
      return this.$store.state.roster.data;
    },
    current_roster() {
      return this.$store.state.activity.selected;
    }
  },
  methods: {
    rosterChange(roster) {
      console.log('RosterChange');
      this.current_roster = roster;
    }

  }
};

</script>
