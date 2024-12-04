<template>
    <div>
        <app-header title="查看订阅源" :show-back="true" @back="$router.push('/')" />

        <v-container>
            <!-- 频道链接格式 -->
            <v-card>
                <v-card-title class="d-flex align-center py-2">
                    订阅源链接(这是配置到直播软件里用的！)
                    <v-spacer></v-spacer>
                </v-card-title>
                <v-expand-transition>
                    <v-card-text class="pa-2">
                        <v-row dense>
                            <v-col v-for="(item, index) in channelUrls" :key="index" cols="12" class="py-1">
                                <v-card class="pa-2" variant="outlined">
                                    <!-- 提示信息 -->
                                    <div class="d-flex align-center mb-1">
                                        <v-icon size="small" color="info" class="mr-1">
                                            mdi-information
                                        </v-icon>
                                        <span class="text-primary text-caption">{{ item.desc }}</span>
                                    </div>

                                    <!-- URL和复制按钮 -->
                                    <div class="d-flex align-center">
                                        <span class="text-body-2">{{ item.url }}</span>
                                        <div class="d-flex">
                                            <v-btn icon="mdi-content-copy" size="x-small" variant="text" class="ml-1"
                                                title="复制链接" @click="copyToClipboard(item.url)"></v-btn>
                                            <v-btn icon="mdi-open-in-new" size="x-small" variant="text" class="ml-1"
                                                title="在新窗口打开" @click="openInNewTab(item.url)"></v-btn>
                                        </div>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-expand-transition>
            </v-card>
        </v-container>
    </div>
</template>

<script>
import AppHeader from '../components/AppHeader.vue';

export default {
    name: 'ChannelUrlsPage',
    components: {
        AppHeader
    },
    props: {
        channelUrls: {
            type: Array,
            required: true
        }
    },
    emits: ['back', 'show-message'],
    setup(props, { emit }) {
        const copyToClipboard = async (text) => {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    // 新版浏览器 API
                    await navigator.clipboard.writeText(text)
                    emit('show-message', '链接已复制到剪贴板', 'success')
                } else {
                    // 兼容旧版浏览器
                    const textArea = document.createElement('textarea')
                    textArea.value = text
                    textArea.style.position = 'fixed'
                    textArea.style.left = '-999999px'
                    textArea.style.top = '-999999px'
                    document.body.appendChild(textArea)
                    textArea.focus()
                    textArea.select()
                    try {
                        document.execCommand('copy')
                        textArea.remove()
                        emit('show-message', '链接已复制到剪贴板~', 'success')
                    } catch (error) {
                        textArea.remove()
                        emit('show-message', '复制失败，请手动复制', 'error')
                    }
                }
            } catch (err) {
                emit('show-message', '复制失败: ' + err.message, 'error')
            }
        }

        const openInNewTab = (url) => {
            window.open(url, '_blank')
        }

        return {
            copyToClipboard,
            openInNewTab
        }
    }
}
</script>