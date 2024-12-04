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
    VDivider,
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
    VTextField,
    VToolbar,
    VToolbarTitle
} from 'vuetify/components'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import 'vuetify/styles'

export default createVuetify({
    components: {
        VAlert,
        VApp,
        VAppBar,
        VAppBarTitle,
        VBtn,
        VCard,
        VCardActions,
        VCardText,
        VCardTitle,
        VCheckbox,
        VChip,
        VCol,
        VContainer,
        VDialog,
        VDivider,
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
        VTextField,
        VToolbar,
        VToolbarTitle
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