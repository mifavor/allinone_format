import { createApp } from 'vue'
import App from './App.vue'
import './assets/styles/global.css'
import vuetify from './plugins/vuetify'

const app = createApp(App)

app
    .use(vuetify)
    .mount('#app')