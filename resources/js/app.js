import "./bootstrap";

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'

import { createApp } from 'vue';
import App from '../../frontend/first-vue-app/src/CardApp.vue';
import VuetifyApp from '../../frontend/deck-optimizer-frontend/src/DeckOptimizerApp.vue';
import CardListingVuetify from '../../frontend/deck-optimizer-frontend/src/components/CardListingVuetify.vue';

import CardListing from '../../frontend/first-vue-app/src/components/CardListing.vue';

//createApp({}).mount("#app");
//const app = createApp({});//.mount("#app");
//app.component("card-listing", CardListing);
//app.mount("#app");

createApp(App).mount('#app');
App.component("card-listing", CardListing);

//const vuetify = createVuetify()
//const VuetifyApp = createApp()
//VuetifyApp.use(vuetify).mount('#deckApp')

createApp(VuetifyApp).use(vuetify).mount('#deckApp')
VuetifyApp.component("card-listing-vuetify", CardListingVuetify);
//VuetifyApp.use(vuetify)

