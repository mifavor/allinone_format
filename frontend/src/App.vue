<template>
    <v-app>
        <v-app-bar color="primary">
            <v-app-bar-title>配置管理</v-app-bar-title>
            <v-spacer></v-spacer>
            <v-btn icon @click="toggleTheme" :title="isDark ? '切换到亮色模式' : '切换到暗色模式'">
                <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- 加载遮罩 -->
        <v-overlay v-model="loading" class="align-center justify-center" persistent scrim="#666666">
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
            <v-container>
                <!-- M3U 地址配置 -->
                <v-card class="mb-6">
                    <v-card-title>配置 allinone tv.m3u 订阅源 *</v-card-title>
                    <v-card-text>
                        <div class="text-error text-h6 font-weight-bold mb-2">
                            下面这个要配置的是 allinone tv.m3u 订阅源链接！
                        </div>
                        <v-text-field ref="tvM3uUrlField" v-model="config.tv_m3u_url"
                            placeholder="eg: http://192.168.31.50:35455/tv.m3u" variant="outlined"
                            :rules="tvM3uUrlRules" persistent-hint hint="请在上面填写 allinone tv.m3u 订阅源"></v-text-field>
                    </v-card-text>
                </v-card>

                <!-- 确认对话框 -->
                <v-dialog v-model="confirmDialog.show" persistent max-width="600">
                    <v-card>
                        <v-card-title class="text-warning text-h4 font-weight-bold">请仔细阅读！！！</v-card-title>
                        <v-card-text class="text-error text-h6 font-weight-bold">
                            当前配置的 allinone tv.m3u 订阅源未包含 /tv.m3u ！<br><br>
                            请仔细查看配置的究竟对不对！！！<br><br>
                            没问题就点 "确定"，有问题就点 "取消" 重新修改。
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn variant="outlined" @click="confirmDialog.show = false">取消</v-btn>
                            <v-btn color="primary" variant="flat" :disabled="confirmDialog.countdown > 0"
                                @click="handleConfirmSave">
                                确定 {{ confirmDialog.countdown ? `(${confirmDialog.countdown}s)` : '' }}
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <!-- 链接输出配置 -->
                <v-card class="mb-6">
                    <v-card-title>链接输出格式配置</v-card-title>
                    <v-card-text>
                        <div class="d-flex flex-wrap gap-4">
                            <v-chip class="ma-2" :color="config.link_output_jump ? 'primary' : 'grey'"
                                variant="outlined">
                                <v-checkbox v-model="config.link_output_jump" label="启用跳转" density="comfortable"
                                    hide-details></v-checkbox>
                            </v-chip>
                            <v-chip class="ma-2" :color="config.link_output_desc ? 'primary' : 'grey'"
                                variant="outlined">
                                <v-checkbox v-model="config.link_output_desc" label="启用备注" density="comfortable"
                                    hide-details></v-checkbox>
                            </v-chip>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- 频道链接格式 -->
                <v-card class="mb-6">
                    <v-card-title class="d-flex align-center py-2">
                        订阅源链接(这是配置到直播软件里用的！)
                        <v-spacer></v-spacer>
                        <v-btn icon color="primary" size="small" @click="expandUrls = !expandUrls">
                            <v-icon>{{ expandUrls ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-expand-transition>
                        <v-card-text v-show="expandUrls" class="pa-2">
                            <v-row dense>
                                <v-col v-for="(item, index) in channelUrls" :key="index" cols="12" :sm="6" :lg="4"
                                    class="py-1">
                                    <v-list-item class="url-item pa-2">
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
                                                <v-btn icon="mdi-content-copy" size="x-small" variant="text"
                                                    class="ml-1" title="复制链接"
                                                    @click="copyToClipboard(item.url)"></v-btn>
                                                <v-btn icon="mdi-open-in-new" size="x-small" variant="text" class="ml-1"
                                                    title="在新窗口打开" @click="openInNewTab(item.url)"></v-btn>
                                            </div>
                                        </div>
                                    </v-list-item>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-expand-transition>
                </v-card>

                <!-- 链接类型配置和排序 -->
                <v-card class="mb-6">
                    <v-card-title>直播源类型配置</v-card-title>
                    <v-card-text>
                        <draggable v-model="linkTypeKeys" item-key="type" class="d-flex flex-wrap gap-2"
                            ghost-class="ghost" @start="drag = true" @end="drag = false">
                            <template #item="{ element: type }">
                                <v-chip class="ma-2" :color="config.link_type[type] ? 'primary' : 'grey'"
                                    variant="outlined" draggable>
                                    <v-checkbox v-model="config.link_type[type]" :label="type" hide-details
                                        density="compact" class="mr-2"></v-checkbox>
                                    <v-icon>mdi-drag</v-icon>
                                </v-chip>
                            </template>
                        </draggable>
                    </v-card-text>
                </v-card>

                <!-- 频道分组映射 -->
                <v-card class="mb-6">
                    <v-card-title class="d-flex align-center">
                        配置输出频道分组
                        <v-spacer></v-spacer>
                        <v-btn color="primary" @click="addNewGroup">
                            添加新分组
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
                        <!-- 原始频道分类（独立一行）-->
                        <v-row>
                            <v-col cols="12">
                                <v-card>
                                    <v-card-title class="d-flex text-warning align-center">
                                        尚未分配或不想要的原始频道分类
                                        <v-spacer></v-spacer>
                                        <v-btn icon color="warning" size="small" title="这些分类中的频道将被忽略">
                                            <v-icon>mdi-alert-circle</v-icon>
                                        </v-btn>
                                    </v-card-title>
                                    <v-card-text>
                                        <draggable :model-value="unmappedChannels" :group="{
                                            name: 'channels',
                                            pull: 'clone',
                                            put: function (to, from) {
                                                // 只允许从 channels 组拖入
                                                return from.options.group.name === 'channels';
                                            }
                                        }" item-key="name" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                                            @start="drag = true" @end="drag = false" @add="handleChannelRemoved">
                                            <template #item="{ element }">
                                                <v-chip class="ma-2" color="primary" variant="outlined" draggable>
                                                    {{ element }}
                                                    <template v-slot:append>
                                                        <v-icon>mdi-drag</v-icon>
                                                    </template>
                                                </v-chip>
                                            </template>
                                        </draggable>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- 输出频道分类 -->
                        <draggable v-model="outputGroupOrder" item-key="name" handle=".group-drag-handle"
                            @start="groupDrag = true" @end="groupDrag = false" tag="div" class="v-row" :animation="200"
                            :group="{ name: 'output-groups', pull: true, put: false }">
                            <template #item="{ element: name }">
                                <v-col cols="12" :lg="6">
                                    <v-card>
                                        <v-card-title class="d-flex align-center">
                                            <v-icon class="mr-2 group-drag-handle" color="grey">
                                                mdi-drag
                                            </v-icon>

                                            <!-- 分组标题（只读/编辑模式切换） -->
                                            <template v-if="editingGroup !== name">
                                                <span class="text-truncate max-width-200">{{ groupNames[name] }}</span>
                                                <v-icon class="ml-2" color="primary" size="small"
                                                    @click="startEdit(name)">
                                                    mdi-pencil
                                                </v-icon>
                                            </template>
                                            <template v-else>
                                                <v-text-field v-model="groupNames[name]"
                                                    :rules="[v => !!v || '分组名称不能为空']" hide-details density="compact"
                                                    class="mr-2" autofocus @blur="finishEdit(name)"
                                                    @keyup.enter="finishEdit(name)"
                                                    @keyup.esc="cancelEdit(name)"></v-text-field>
                                            </template>

                                            <v-spacer></v-spacer>

                                            <v-btn icon color="error" size="small" @click="deleteGroup(name)"
                                                :disabled="Object.keys(config.output_channel_group).length <= 1">
                                                <v-icon>mdi-delete</v-icon>
                                            </v-btn>
                                        </v-card-title>
                                        <v-card-text>
                                            <draggable v-model="config.output_channel_group[name]" :group="{
                                                name: 'channels',
                                                pull: true,
                                                put: function (to, from) {
                                                    // 只允许从 channels 组拖入
                                                    return from.options.group.name === 'channels';
                                                }
                                            }" item-key="name" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                                                :animation="200" @start="drag = true" @end="drag = false"
                                                @dragenter="dragEnterGroup = name" @dragleave="dragEnterGroup = null"
                                                @drop="dragEnterGroup = null">

                                                <!-- 频道列表 -->
                                                <template #item="{ element }">
                                                    <v-chip class="ma-2" color="primary" variant="outlined" draggable>
                                                        {{ element }}
                                                        <template v-slot:append>
                                                            <v-icon>mdi-drag</v-icon>
                                                        </template>
                                                    </v-chip>
                                                </template>

                                            </draggable>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </template>
                        </draggable>
                    </v-card-text>
                </v-card>

                <!-- 保存按钮 -->
                <div class="save-button-container">
                    <v-btn color="primary" size="large" :disabled="saving" @click="saveConfig" class="save-button">
                        保存配置
                    </v-btn>
                </div>

                <!-- 结果提示 -->
                <v-dialog v-model="message.show" width="300" :persistent="false" :timeout="2000">
                    <v-alert :type="message.color === 'success' ? 'success' : 'error'" class="mb-0">
                        <div class="message-text">{{ message.text }}</div>
                    </v-alert>
                </v-dialog>
            </v-container>
        </v-main>

        <!-- 保存遮罩 -->
        <v-overlay v-model="saving" class="align-center justify-center" persistent scrim="#666666">
            <v-card class="pa-8" width="300">
                <div class="text-center mb-4">
                    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                </div>
                <div class="text-h6 text-center">
                    正在保存配置...
                </div>
            </v-card>
        </v-overlay>

        <!-- 添加频道对话框 -->
        <v-dialog v-model="dialog.show" max-width="500px">
            <v-card>
                <v-card-title>添加频道分类</v-card-title>
                <v-card-text>
                    <v-select v-model="dialog.selectedChannels" :items="getAvailableChannels(dialog.groupName)"
                        label="选择要添加的频道分类" multiple chips></v-select>
                </v-card-text>
                <v-card-text class="text-right">
                    <v-btn color="primary" @click="addChannelsToGroup">确定</v-btn>
                    <v-btn color="grey" @click="dialog.show = false">取消</v-btn>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-app>
</template>

<script>
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import draggable from 'vuedraggable';
import { useTheme } from 'vuetify';
import { getChannelUrls } from './api';

export default {
    name: 'App',
    components: {
        draggable
    },
    setup() {
        const config = ref({
            tv_m3u_url: '',
            link_output_jump: false,
            link_output_desc: false,
            link_type: {},
            origin_channel_group: [],
            output_channel_group: {}
        })
        const saving = ref(false)
        const drag = ref(false)
        const editingGroup = ref(null)
        const dragEnterGroup = ref(null)
        const groupNames = ref({})
        const message = ref({
            show: false,
            text: '',
            color: 'success'
        })
        const dialog = ref({
            show: false,
            groupName: '',
            selectedChannels: []
        })
        const groupDrag = ref(false)
        const expandUrls = ref(true)
        const channelUrls = ref([])
        const theme = useTheme()
        const tvM3uUrlField = ref(null)
        const confirmDialog = ref({
            show: false,
            countdown: 5
        })
        let countdownTimer = null
        const loading = ref(false)
        const tvM3uUrlRules = [
            v => !!v || 'allinone tv.m3u 订阅源必须设置',
            v => /^https?:\/\/.+/i.test(v) || '请输入有效的 http/https 链接',
            v => !v.includes(':35456') || '不能使用本服务的端口(35456)'
        ]

        // 监听系统主题变化
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)')

        const updateTheme = (e) => {
            const isDark = e.matches
            theme.global.name.value = isDark ? 'dark' : 'light'
            // 同步更新 html 类
            document.documentElement.classList.toggle('dark', isDark)
        }

        // 初始化主题
        onMounted(async () => {
            updateTheme(prefersDark)
            prefersDark.addEventListener('change', updateTheme)
            loading.value = true
            try {
                await Promise.all([
                    loadConfig(),
                    loadChannelUrls()
                ])
            } finally {
                loading.value = false
            }
        })

        // 清理监听器
        onUnmounted(() => {
            prefersDark.removeEventListener('change', updateTheme)
            if (countdownTimer) {
                clearInterval(countdownTimer)
            }
        })

        // 计算当前是否是暗色模式
        const isDark = computed(() => theme.global.current.value.dark)

        // 手动切换主题
        const toggleTheme = () => {
            theme.global.name.value = isDark.value ? 'light' : 'dark'
        }

        // 输出分组顺序
        const outputGroupOrder = computed({
            get: () => Object.keys(config.value.output_channel_group),
            set: (newOrder) => {
                const newOutputGroup = {}
                newOrder.forEach(key => {
                    newOutputGroup[key] = config.value.output_channel_group[key]
                })
                config.value.output_channel_group = newOutputGroup
            }
        })

        // 显示提示消息
        const showMessage = (text, color = 'success') => {
            message.value.show = true
            message.value.text = text
            message.value.color = color
            setTimeout(() => {
                message.value.show = false
            }, 2000)
        }

        // 计算未映射的频道
        const unmappedChannels = computed(() => {
            if (!config.value.origin_channel_group) return []
            const mappedGroups = new Set()
            Object.values(config.value.output_channel_group).forEach(groups => {
                groups.forEach(group => mappedGroups.add(group))
            })
            return config.value.origin_channel_group.filter(group => !mappedGroups.has(group))
        })

        // 处理频道被移回原始分类
        const handleChannelRemoved = (evt) => {
            // 从所有输出分组中移除该频道
            Object.values(config.value.output_channel_group).forEach(groups => {
                const index = groups.indexOf(evt.item.textContent.trim())
                if (index !== -1) {
                    groups.splice(index, 1)
                }
            })
        }

        // 添加新的输出分组
        const addNewGroup = () => {
            const newGroupName = `新分组${Object.keys(config.value.output_channel_group).length + 1}`
            const newOutputGroup = {
                ...config.value.output_channel_group,
                [newGroupName]: []
            }
            config.value.output_channel_group = newOutputGroup
            groupNames.value[newGroupName] = newGroupName
        }

        // 更新分组名称
        const updateGroupName = (oldName) => {
            const newName = groupNames.value[oldName]
            if (newName && newName !== oldName) {
                const newOutputGroup = {}
                outputGroupOrder.value.forEach(name => {
                    if (name === oldName) {
                        newOutputGroup[newName] = config.value.output_channel_group[oldName]
                        groupNames.value[newName] = newName
                        delete groupNames.value[oldName]
                    } else {
                        newOutputGroup[name] = config.value.output_channel_group[name]
                    }
                })
                config.value.output_channel_group = newOutputGroup
            }
        }

        // 删除分组
        const deleteGroup = (name) => {
            delete config.value.output_channel_group[name]
            delete groupNames.value[name]
        }

        // 加载配置
        const loadConfig = async () => {
            try {
                loading.value = true
                const response = await axios.get('/api/config')
                config.value = response.data
                // 初始化分组名称
                Object.keys(config.value.output_channel_group).forEach(name => {
                    groupNames.value[name] = name
                })
            } catch (error) {
                showMessage('加载配置失败: ' + error.message, 'error')
            } finally {
                loading.value = false
            }
        }

        // 验证配置
        const validateConfig = () => {
            try {
                // 验证 tv_m3u_url
                if (!config.value.tv_m3u_url) {
                    throw new Error('请检查 allinone tv.m3u 订阅源链接配置')
                }
                // 验证 URL 格式
                if (!/^https?:\/\/.+/i.test(config.value.tv_m3u_url)) {
                    throw new Error('请检查 allinone tv.m3u 订阅源链接配置')
                }
                // 验证端口
                if (config.value.tv_m3u_url.includes(':35456')) {
                    throw new Error('tv.m3u 地址不能使用本服务的端口(35456)')
                }

                // 验证链接类型配置
                const enabledLinkTypes = Object.values(config.value.link_type).filter(enabled => enabled)
                if (enabledLinkTypes.length === 0) {
                    throw new Error('至少需要启用一个直播源类型')
                }

                // 验证至少有一个输出分组
                if (Object.keys(config.value.output_channel_group).length === 0) {
                    throw new Error('至少需要一个输出频道分组')
                }

                // 证每个输出分组至少包含一个原始分组
                for (const [name, groups] of Object.entries(config.value.output_channel_group)) {
                    if (groups.length === 0) {
                        throw new Error(`输出分组 "${name}" 至少需要包含一个原始频道分组`)
                    }
                }
            } catch (error) {
                showMessage(error.message, 'error')
                return false
            }
            return true
        }

        // 保存配置
        const saveConfig = async () => {
            try {
                // 检查 tv.m3u 链接
                if (!config.value.tv_m3u_url.endsWith('/tv.m3u')) {
                    // 先验证配置
                    validateConfig()
                    confirmDialog.value.show = true
                    startCountdown()
                    return
                }

                await doSaveConfig()
            } catch (error) {
                showMessage(
                    error.response?.data?.error || error.message || '保存失败',
                    'error'
                )
            }
        }

        // 实际执行保存的方法
        const doSaveConfig = async () => {
            try {
                saving.value = true
                validateConfig()
                await axios.post('/api/config', config.value)
                showMessage('配置保存成功')
            } catch (error) {
                showMessage(
                    error.response?.data?.error || error.message || '保存失败',
                    'error'
                )
            } finally {
                saving.value = false
            }
        }

        const handleConfirmSave = async () => {
            confirmDialog.value.show = false
            clearInterval(countdownTimer)
            await doSaveConfig()
        }

        // 获取可用的频道分类（排除已被其他分组使用的）
        const getAvailableChannels = (groupName) => {
            const mappedGroups = new Set()
            Object.entries(config.value.output_channel_group).forEach(([name, groups]) => {
                if (name !== groupName) {
                    groups.forEach(group => mappedGroups.add(group))
                }
            })
            return config.value.origin_channel_group.filter(group =>
                !mappedGroups.has(group)
            )
        }

        // 添加频道到分组
        const addChannelsToGroup = () => {
            if (dialog.value.selectedChannels.length) {
                config.value.output_channel_group[dialog.value.groupName].push(
                    ...dialog.value.selectedChannels
                )
            }
            dialog.value.show = false
        }

        // 开始编辑分组名称
        const startEdit = (groupName) => {
            tempGroupName.value = groupNames.value[groupName]
            editingGroup.value = groupName
        }

        // 完成编辑
        const finishEdit = (oldName) => {
            const newName = groupNames.value[oldName]
            if (newName && newName !== oldName && newName !== tempGroupName.value) {
                updateGroupName(oldName)
            } else {
                // 如果没有改变或者为空，恢复原名称
                groupNames.value[oldName] = tempGroupName.value
            }
            editingGroup.value = null
        }

        // 取消编辑
        const cancelEdit = (groupName) => {
            groupNames.value[groupName] = tempGroupName.value
            editingGroup.value = null
        }

        // 复制到剪贴板
        const copyToClipboard = async (text) => {
            try {
                // 尝试使用 Clipboard API
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text)
                    showMessage('链接已复制到剪贴板', 'success')
                    return
                }

                // 后备方案：使用 textarea
                const textArea = document.createElement('textarea')
                textArea.value = text

                // 防止滚动
                textArea.style.position = 'fixed'
                textArea.style.left = '-999999px'
                textArea.style.top = '-999999px'

                document.body.appendChild(textArea)
                textArea.focus()
                textArea.select()

                try {
                    // 执行复制命令
                    const successful = document.execCommand('copy')
                    if (successful) {
                        showMessage('链接已复制到剪贴板~', 'success')
                    } else {
                        throw new Error('复制失败')
                    }
                } catch (err) {
                    showMessage('复制失败: ' + err.message, 'error')
                } finally {
                    // 清理
                    document.body.removeChild(textArea)
                }
            } catch (err) {
                showMessage('复制失败: ' + err.message, 'error')
            }
        }

        // 加载频道链接
        const loadChannelUrls = async () => {
            try {
                loading.value = true
                channelUrls.value = await getChannelUrls()
            } catch (error) {
                showMessage(error.message, 'error')
            } finally {
                loading.value = false
            }
        }

        // 在新标签页打开链接
        const openInNewTab = (url) => {
            window.open(url, '_blank')
        }

        // 在 setup 中添加
        const linkTypeKeys = computed({
            get: () => Object.keys(config.value.link_type),
            set: (newOrder) => {
                // 根据新顺序重建 link_type 对象
                const newLinkType = {}
                newOrder.forEach(key => {
                    newLinkType[key] = config.value.link_type[key]
                })
                // 直接更新 config.value.link_type
                config.value.link_type = newLinkType
            }
        })

        const startCountdown = () => {
            confirmDialog.value.countdown = 5
            countdownTimer = setInterval(() => {
                if (confirmDialog.value.countdown > 0) {
                    confirmDialog.value.countdown--
                } else {
                    clearInterval(countdownTimer)
                }
            }, 1000)
        }

        return {
            config,
            saving,
            drag,
            editingGroup,
            dragEnterGroup,
            groupNames,
            message,
            dialog,
            unmappedChannels,
            handleChannelRemoved,
            addNewGroup,
            updateGroupName,
            deleteGroup,
            saveConfig,
            getAvailableChannels,
            outputGroupOrder,
            groupDrag,
            expandUrls,
            channelUrls,
            copyToClipboard,
            openInNewTab,
            toggleTheme,
            tvM3uUrlField,
            linkTypeKeys,
            isDark,
            confirmDialog,
            handleConfirmSave,
            startCountdown,
            doSaveConfig,
            loading,
            tvM3uUrlRules,
            validateConfig
        }
    }
}
</script>

<style>
.gap-2 {
    gap: 8px;
    min-height: 48px;
    /* 保持一个最小高度以便于拖放 */
}

.ghost {
    opacity: 0.5;
    background: #c8ebfb !important;
}

.v-overlay__scrim {
    background: rgba(0, 0, 0, 0.7) !important;
}

.v-overlay__content {
    transition: transform 0.3s ease-in-out;
}

.v-overlay__content:hover {
    transform: scale(1.02);
}

.v-dialog--active {
    pointer-events: none;
    /* 防止鼠标悬停时阻止自动关闭 */
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

.flip-list-move {
    transition: transform 0.5s;
}

.flip-list-enter-active,
.flip-list-leave-active {
    transition: all 0.5s;
}

.flip-list-enter-from,
.flip-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.url-item {
    border: 1px solid rgba(var(--v-border-color), 0.12);
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 2px;
    background-color: rgb(var(--v-theme-surface));
    transition: box-shadow 0.2s;
}

.url-item:hover {
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.save-button-container {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to bottom,
            rgba(var(--v-theme-background), 0) 0%,
            rgba(var(--v-theme-background), 0.8) 40%,
            rgba(var(--v-theme-background), 0.95) 100%);
    backdrop-filter: blur(8px);
    text-align: center;
    z-index: 100;
    border-top: 1px solid rgba(var(--v-border-color), 0.12);
}

.save-button {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    min-width: 120px;
    font-weight: 500;
}

/* 为了防止保存按钮遮挡内容，给容器添加底部内边距 */
.v-container {
    padding-bottom: 80px !important;
}

/* 让结果提示支持换行 */
.message-text {
    white-space: pre-line;
    word-break: break-word;
}
</style>