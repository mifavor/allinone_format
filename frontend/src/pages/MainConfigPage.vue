<template>
    <div>
        <app-header title="配置管理" />

        <v-container>
            <!-- M3U 地址配置 -->
            <v-card class="mb-6">
                <v-card-title>配置 allinone tv.m3u 订阅源</v-card-title>
                <v-card-text>
                    <div class="text-error mb-2">
                        下面这个要配置的是 allinone tv.m3u 订阅源链接！<br>
                        格式要求：<br>
                        1、不能为空, 必须设置<br>
                        2、必须是 http 或 https 协议<br>
                        3、不能使用本服务的端口(35456)<br>
                        4、支持带反向代理参数 url=https?://allinone代理域名[:端口]
                    </div>
                    <v-text-field ref="tvM3uUrlField" v-model="config.tv_m3u_url"
                        placeholder="eg: http://内网IP:35455/tv.m3u" variant="outlined" :rules="tvM3uUrlRules"
                        persistent-hint hint="请在上面填写 allinone tv.m3u 订阅源">
                    </v-text-field>
                </v-card-text>
                <v-card-text class="text-center">
                    <v-btn color="primary" block :loading="saving" @click="save">
                        保存配置
                    </v-btn>
                </v-card-text>
            </v-card>

            <!-- 功能入口按钮 -->
            <v-card>
                <v-card-text>
                    <v-row justify="center" class="gap-y-4">
                        <v-col cols="12" sm="auto">
                            <v-btn color="primary" block @click="switchComponent('ChannelUrlsPage')">
                                查看订阅源
                            </v-btn>
                        </v-col>

                        <v-col cols="12" sm="auto">
                            <v-btn color="primary" block @click="switchComponent('BasicConfigPage')">
                                基础配置
                            </v-btn>
                        </v-col>

                        <v-col cols="12" sm="auto">
                            <v-btn color="primary" block @click="switchComponent('GroupConfigPage')">
                                分组配置
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </v-container>

        <!-- 确认对话框 -->
        <v-dialog v-model="confirmDialog.show" width="500" persistent>
            <v-card>
                <v-card-text class="pa-4">
                    <div class="d-flex align-center mb-4">
                        <v-icon color="warning" size="large" class="mr-3">
                            mdi-alert
                        </v-icon>
                        <div class="text-pre-line text-break">{{ confirmDialog.message }}</div>
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="handleConfirmDialog(false)">
                        取消
                    </v-btn>
                    <v-btn color="warning" @click="handleConfirmDialog(true)">
                        确定
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { ref } from 'vue';
import { VDivider } from 'vuetify/components';
import { updateConfig } from '../api';
import AppHeader from '../components/AppHeader.vue';

export default {
    name: 'MainConfigPage',
    components: {
        AppHeader,
        VDivider
    },
    props: {
        config: {
            type: Object,
            required: true
        }
    },
    emits: ['show-basic-config', 'show-channel-urls', 'show-group-config', 'show-message', 'switch-component'],
    setup(props, { emit }) {
        const saving = ref(false)
        const tvM3uUrlField = ref(null)
        const tvM3uUrlRules = [
            v => !!v || 'allinone tv.m3u 订阅源必须设置',
            v => /^https?:\/\/.+/i.test(v) || '必须是 http 或 https 协议',
            v => !v.includes(':35456') || '不能使用本服务的端口(35456)'
        ]

        const confirmDialog = ref({
            show: false,
            message: '',
            resolve: null
        })

        // 验证订阅源配置
        const validateConfig = () => {
            // 验证 tv_m3u_url
            const tvM3uUrlErrors = tvM3uUrlRules
                .map(rule => rule(props.config.tv_m3u_url))
                .filter(result => result !== true); // 只保留错误消息

            if (tvM3uUrlErrors.length > 0) {
                throw new Error('tv.m3u 链接格式错误:\n' + tvM3uUrlErrors.join('\n')); // 抛出所有错误消息
            }
        }

        // 显示确认对话框
        const showConfirmDialog = (message) => {
            return new Promise(resolve => {
                confirmDialog.value = {
                    show: true,
                    message,
                    resolve
                }
            })
        }

        // 处理确认对话框的结果
        const handleConfirmDialog = (result) => {
            confirmDialog.value.show = false
            if (confirmDialog.value.resolve) {
                confirmDialog.value.resolve(result)
            }
        }

        const save = async () => {
            try {
                validateConfig()

                // 检查是否包含 /tv.m3u
                if (!props.config.tv_m3u_url.includes('/tv.m3u')) {
                    const confirmed = await showConfirmDialog(
                        '检测到订阅源链接不包含 /tv.m3u，这可能不是正确的订阅源链接。\n\n' +
                        '如果你确定这是正确的链接，请点击"确定"继续。\n\n' +
                        '如果不确定，请点击"取消"检查订阅源链接是否正确。'
                    )
                    if (!confirmed) {
                        return
                    }
                }

                saving.value = true
                // 只提交本页面相关的配置
                const configToSave = {
                    tv_m3u_url: props.config.tv_m3u_url
                }
                await updateConfig(configToSave)
                emit('show-message', '配置保存成功', 'success')
            } catch (error) {
                emit('show-message', error.response?.data?.error || error.message || '保存失败', 'error')
            } finally {
                saving.value = false
            }
        }

        const switchComponent = (componentName) => {
            emit('switch-component', componentName);
        };

        return {
            saving,
            tvM3uUrlField,
            tvM3uUrlRules,
            confirmDialog,
            handleConfirmDialog,
            save,
            switchComponent
        }
    }
}
</script>