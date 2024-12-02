import { createApp } from 'vue'
import App from './App.vue'

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import {
    VAlert,
    VApp, VAppBar, VAppBarTitle,
    VBtn,
    VCard, VCardActions, VCardText, VCardTitle,
    VCheckbox,
    VChip,
    VCol,
    VContainer,
    VDialog,
    VExpandTransition,
    VIcon,
    VList,
    VListItem,
    VMain,
    VOverlay,
    VProgressCircular,
    VRow,
    VScaleTransition,
    VSelect,
    VSnackbar,
    VSpacer,
    VTextField
} from 'vuetify/components'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import 'vuetify/styles'

const vuetify = createVuetify({
    components: {
        VApp,
        VAppBar,
        VAppBarTitle,
        VMain,
        VContainer,
        VCard,
        VCardTitle,
        VCardText,
        VCardActions,
        VTextField,
        VBtn,
        VSnackbar,
        VRow,
        VCol,
        VCheckbox,
        VIcon,
        VChip,
        VSpacer,
        VSelect,
        VList,
        VListItem,
        VDialog,
        VOverlay,
        VProgressCircular,
        VAlert,
        VExpandTransition,
        VScaleTransition
    },
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    primary: '#1867C0',
                }
            }
        }
    }
})

const app = createApp(App)
app.use(vuetify)
app.mount('#app') 