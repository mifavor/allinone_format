<template>
    <div>
        <app-header title="基础配置" :show-back="true" @back="$router.push('/')" />

        <v-container>
            <!-- 链接输出配置 -->
            <v-card class="mb-6">
                <v-card-title>链接输出格式配置</v-card-title>
                <v-card-text>
                    <div class="d-flex flex-wrap gap-4">
                        <v-chip class="ma-2" :color="config.link_output_jump ? 'primary' : 'grey'" variant="outlined">
                            <v-checkbox v-model="config.link_output_jump" label="启用跳转" density="comfortable"
                                hide-details></v-checkbox>
                        </v-chip>
                        <v-chip class="ma-2" :color="config.link_output_desc ? 'primary' : 'grey'" variant="outlined">
                            <v-checkbox v-model="config.link_output_desc" label="启用备注" density="comfortable"
                                hide-details></v-checkbox>
                        </v-chip>
                    </div>
                </v-card-text>
            </v-card>

            <!-- 链接类型配置和排序 -->
            <v-card class="mb-6">
                <v-card-title>直播源类型配置</v-card-title>
                <v-card-text>
                    <draggable v-model="linkTypeKeys" item-key="type" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                        @start="drag = true" @end="drag = false">
                        <template #item="{ element: type }">
                            <v-chip class="ma-2" :color="config.link_type[type] ? 'primary' : 'grey'" variant="outlined"
                                draggable>
                                <v-checkbox v-model="config.link_type[type]" :label="type" hide-details
                                    density="compact" class="mr-2"></v-checkbox>
                                <v-icon>mdi-drag</v-icon>
                            </v-chip>
                        </template>
                    </draggable>
                </v-card-text>
            </v-card>

            <!-- 保存按钮 -->
            <v-card>
                <v-card-text class="text-center">
                    <v-btn color="primary" block :loading="saving" @click="save">
                        保存配置
                    </v-btn>
                </v-card-text>
            </v-card>
        </v-container>
    </div>
</template>

<script>
import { computed, ref } from 'vue';
import draggable from 'vuedraggable';
import { updateConfig } from '../api';
import AppHeader from '../components/AppHeader.vue';

export default {
    name: 'BasicConfigPage',
    components: { draggable, AppHeader },
    props: {
        config: {
            type: Object,
            required: true
        }
    },
    emits: ['back', 'show-message'],
    setup(props, { emit }) {
        const saving = ref(false)
        const drag = ref(false)

        // 验证基础配置
        const validateConfig = () => {
            // 验证是否启用了至少一种直播源类型
            if (!Object.values(props.config.link_type).some(enabled => enabled)) {
                throw new Error('至少需要启用一种直播源类型')
            }
        }

        const linkTypeKeys = computed({
            get: () => Object.keys(props.config.link_type),
            set: (newOrder) => {
                const newLinkType = {}
                newOrder.forEach(key => {
                    newLinkType[key] = props.config.link_type[key]
                })
                props.config.link_type = newLinkType
            }
        })

        const save = async () => {
            try {
                validateConfig()

                saving.value = true
                // 只提交本页面相关的配置
                const configToSave = {
                    link_output_jump: props.config.link_output_jump,
                    link_output_desc: props.config.link_output_desc,
                    link_type: props.config.link_type
                }
                await updateConfig(configToSave)
                emit('show-message', '配置保存成功', 'success')
            } catch (error) {
                emit('show-message', error.response?.data?.error || error.message || '保存失败', 'error')
            } finally {
                saving.value = false
            }
        }

        return {
            saving,
            drag,
            linkTypeKeys,
            save
        }
    }
}
</script>