import { createApp } from 'vue'
import App from './App.vue'

// Vuetify
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import {
    VAlert,
    VApp, VAppBar, VAppBarTitle,
    VBtn,
    VCard,
    VCardText,
    VCardTitle,
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
        VExpandTransition
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
                dark: false,
                colors: {
                    background: '#FFFFFF',
                    surface: '#FFFFFF',
                    primary: '#1867C0',
                    secondary: '#5CBBF6',
                    error: '#FF5252',
                    info: '#2196F3',
                    success: '#4CAF50',
                    warning: '#FB8C00',
                }
            },
            dark: {
                dark: true,
                colors: {
                    background: '#121212',
                    surface: '#212121',
                    primary: '#2196F3',
                    secondary: '#64B5F6',
                    error: '#FF5252',
                    info: '#2196F3',
                    success: '#4CAF50',
                    warning: '#FB8C00',
                }
            }
        }
    }
})

const app = createApp(App)
app.use(vuetify)
app.mount('#app') 