import { createApp } from 'vue'
import App from './App.vue'
import './assets/styles/global.css'
import vuetify from './plugins/vuetify'
import router from './router'

const app = createApp(App)

app
    .use(vuetify)
    .use(router)
    .mount('#app') 