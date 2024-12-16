<template>
    <v-app>
        <message-dialog ref="messageDialog" />

        <!-- 加载遮罩 -->
        <v-overlay v-model="loading" class="align-center justify-center" persistent scrim="rgba(0, 0, 0, 0.7)">
            <v-card class="pa-8" width="300">
                <div class="text-center mb-4">
                    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                </div>
                <div class="text-h6 text-center">
                    正在加载配置...
                </div>
            </v-card>
        </v-overlay>

        <!-- 内容区域 -->
        <v-main v-if="!loading">
            <component :is="currentComponent" :config="config" :channel-urls="channelUrls" @show-message="showMessage"
                @switch-component="switchComponent" />
        </v-main>
    </v-app>
</template>

<script>
import { onMounted, ref } from 'vue';
import { getChannelUrls, getConfig } from './api';
import MessageDialog from './components/MessageDialog.vue';
import BasicConfigPage from './pages/BasicConfigPage.vue'; // 导入需要的页面组件
import ChannelUrlsPage from './pages/ChannelUrlsPage.vue'; // 导入需要的页面组件
import GroupConfigPage from './pages/GroupConfigPage.vue'; // 导入需要的页面组件
import MainConfigPage from './pages/MainConfigPage.vue'; // 导入需要的页面组件

export default {
    name: 'App',
    components: {
        MessageDialog,
        ChannelUrlsPage,
        MainConfigPage,
        BasicConfigPage,
        GroupConfigPage
    },
    setup() {
        const config = ref({
            tv_m3u_url: '',
            link_output_jump: false,
            link_output_desc: false,
            link_type: {},
            origin_channel_group: [],
            output_channel_group: {}
        });
        const loading = ref(false);
        const channelUrls = ref([]);
        const messageDialog = ref(null);
        const currentComponent = ref('MainConfigPage'); // 设置默认显示的组件为 MainConfigPage

        // 初始化主题
        onMounted(async () => {
            loading.value = true;
            try {
                await Promise.all([
                    loadConfig(),
                    loadChannelUrls()
                ]);
            } finally {
                loading.value = false;
            }
        });

        const showMessage = (text, type = 'success') => {
            if (messageDialog.value) {
                messageDialog.value.showMessage(text, type);
            }
        };

        // 加载配置
        const loadConfig = async () => {
            try {
                config.value = await getConfig();
            } catch (error) {
                showMessage('加载配置失败: ' + error.message, 'error');
            }
        };

        // 加载频道链接
        const loadChannelUrls = async () => {
            try {
                channelUrls.value = await getChannelUrls();
            } catch (error) {
                showMessage(error.message, 'error');
            }
        };

        // 切换组件
        const switchComponent = (componentName) => {
            currentComponent.value = componentName;
        };

        return {
            config,
            loading,
            channelUrls,
            messageDialog,
            showMessage,
            currentComponent,
            switchComponent // 返回切换组件的方法
        };
    }
}
</script>

<style>
.ghost {
    opacity: 0.5;
    background: rgb(var(--v-theme-primary)) !important;
    border: 2px dashed rgba(var(--v-theme-primary), 0.5);
}

.group-drag-handle {
    cursor: move;
}

.text-truncate {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>