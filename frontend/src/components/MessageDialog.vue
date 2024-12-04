<template>
    <v-dialog v-model="show" width="500" persistent>
        <v-card>
            <v-card-text class="pa-4">
                <div class="d-flex align-center mb-4">
                    <v-icon :color="type === 'success' ? 'success' : 'error'" size="large" class="mr-3">
                        {{ type === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle' }}
                    </v-icon>
                    <div class="text-pre-line text-break">{{ text }}</div>
                </div>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn :color="type === 'success' ? 'success' : 'error'" variant="flat" @click="show = false"
                    min-width="100">
                    确定
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { ref } from 'vue';
import { VDivider } from 'vuetify/components';

export default {
    name: 'MessageDialog',
    components: {
        VDivider
    },
    setup() {
        const show = ref(false)
        const text = ref('')
        const type = ref('success')

        const showMessage = (message, messageType = 'success') => {
            text.value = message
            type.value = messageType
            show.value = true
        }

        return {
            show,
            text,
            type,
            showMessage
        }
    }
}
</script>