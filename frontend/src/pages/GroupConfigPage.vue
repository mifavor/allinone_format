<template>
    <div>
        <app-header title="分组配置" :show-back="true" @back="$router.push('/')" />

        <v-container>
            <!-- 频道分组映射 -->
            <v-card>
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
                        <v-col cols="12" :lg="6">
                            <v-card>
                                <v-card-title class="d-flex text-warning align-center">
                                    未分配的原始频道分类
                                    <v-spacer></v-spacer>
                                    <v-btn icon color="warning" size="small" title="这些分类将组成 <其他频道>">
                                        <v-icon>mdi-alert-circle</v-icon>
                                    </v-btn>
                                </v-card-title>
                                <v-card-text>
                                    <draggable :model-value="unmappedChannels" :group="{
                                        name: 'channels',
                                        pull: 'clone',
                                        put: function (to, from) {
                                            return from.options.group.name === 'channels';
                                        }
                                    }" item-key="name" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                                        @start="drag = true" @end="drag = false" @add="handleChannelRemoved"
                                        style="min-height: 50px;">
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
                        <v-col cols="12" :lg="6">
                            <v-card>
                                <v-card-title class="d-flex text-error align-center">
                                    想删除的原始频道分类
                                    <v-spacer></v-spacer>
                                    <v-btn icon color="error" size="small" title="这些分类将被从输出结果中删除">
                                        <v-icon>mdi-delete-forever</v-icon>
                                    </v-btn>
                                </v-card-title>
                                <v-card-text>
                                    <draggable v-model="config.deprecated_origin_channel_group" :group="{
                                        name: 'channels',
                                        pull: true,
                                        put: function (to, from) {
                                            return from.options.group.name === 'channels';
                                        }
                                    }" item-key="name" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                                        @start="drag = true" @end="drag = false" style="min-height: 50px;">
                                        <template #item="{ element }">
                                            <v-chip class="ma-2" color="error" variant="outlined" draggable>
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
                                            <v-icon class="ml-2" color="primary" size="small" @click="startEdit(name)">
                                                mdi-pencil
                                            </v-icon>
                                        </template>
                                        <template v-else>
                                            <v-text-field v-model="groupNames[name]" :rules="[v => !!v || '分组名称不能为空']"
                                                hide-details density="compact" class="mr-2" autofocus
                                                @blur="finishEdit(name)" @keyup.enter="finishEdit(name)"
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
                                                return from.options.group.name === 'channels';
                                            }
                                        }" item-key="name" class="d-flex flex-wrap gap-2" ghost-class="ghost"
                                            :animation="200" @start="drag = true" @end="drag = false"
                                            @dragenter="dragEnterGroup = name" @dragleave="dragEnterGroup = null"
                                            @drop="dragEnterGroup = null" style="min-height: 50px;">

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
            <v-card class="mt-6">
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
    name: 'GroupConfigPage',
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
        const groupDrag = ref(false)
        const editingGroup = ref(null)
        const dragEnterGroup = ref(null)
        const groupNames = ref({})
        const tempGroupName = ref('')

        // 初始化分组名称
        Object.keys(props.config.output_channel_group).forEach(name => {
            groupNames.value[name] = name
        })

        const outputGroupOrder = computed({
            get: () => Object.keys(props.config.output_channel_group),
            set: (newOrder) => {
                const newOutputGroup = {}
                newOrder.forEach(key => {
                    newOutputGroup[key] = props.config.output_channel_group[key]
                })
                props.config.output_channel_group = newOutputGroup
            }
        })

        const unmappedChannels = computed(() => {
            if (!props.config.origin_channel_group) return []
            const mappedGroups = new Set()
            Object.values(props.config.output_channel_group).forEach(groups => {
                groups.forEach(group => mappedGroups.add(group))
            })
            props.config.deprecated_origin_channel_group.forEach(group => {
                mappedGroups.add(group)
            })
            return props.config.origin_channel_group.filter(group => !mappedGroups.has(group))
        })

        const handleChannelRemoved = (evt) => {
            Object.values(props.config.output_channel_group).forEach(groups => {
                const index = groups.indexOf(evt.item.textContent.trim())
                if (index !== -1) {
                    groups.splice(index, 1)
                }
            })
        }

        const addNewGroup = () => {
            const newGroupName = `新分组${Object.keys(props.config.output_channel_group).length + 1}`
            const newOutputGroup = {
                ...props.config.output_channel_group,
                [newGroupName]: []
            }
            props.config.output_channel_group = newOutputGroup
            groupNames.value[newGroupName] = newGroupName
        }

        const updateGroupName = (oldName) => {
            const newName = groupNames.value[oldName]
            if (newName && newName !== oldName) {
                const newOutputGroup = {}
                outputGroupOrder.value.forEach(name => {
                    if (name === oldName) {
                        newOutputGroup[newName] = props.config.output_channel_group[oldName]
                        groupNames.value[newName] = newName
                        delete groupNames.value[oldName]
                    } else {
                        newOutputGroup[name] = props.config.output_channel_group[name]
                    }
                })
                props.config.output_channel_group = newOutputGroup
            }
        }

        const deleteGroup = (name) => {
            delete props.config.output_channel_group[name]
            delete groupNames.value[name]
        }

        const startEdit = (groupName) => {
            tempGroupName.value = groupNames.value[groupName]
            editingGroup.value = groupName
        }

        const finishEdit = (oldName) => {
            const newName = groupNames.value[oldName]
            if (newName && newName !== oldName && newName !== tempGroupName.value) {
                updateGroupName(oldName)
            } else {
                groupNames.value[oldName] = tempGroupName.value
            }
            editingGroup.value = null
        }

        const cancelEdit = (groupName) => {
            groupNames.value[groupName] = tempGroupName.value
            editingGroup.value = null
        }

        // 验证分组配置
        const validateConfig = () => {
            // 检查是否有分组
            if (Object.keys(props.config.output_channel_group).length === 0) {
                throw new Error('至少需要创建一个分组')
            }

            // 检查分组名称是否为空
            for (const name in groupNames.value) {
                if (!groupNames.value[name].trim()) {
                    throw new Error('分组名称不能为空')
                }
            }

            // 检查分组名称是否重复
            const names = new Set(Object.values(groupNames.value))
            if (names.size !== Object.keys(groupNames.value).length) {
                throw new Error('分组名称不能重复')
            }

            // 检查每个分组是否至少包含一个原始频道分类
            for (const [groupName, channels] of Object.entries(props.config.output_channel_group)) {
                if (!channels || channels.length === 0) {
                    throw new Error(`分组 "${groupName}" 至少需要包含一个原始频道分类`)
                }
            }
        }

        const save = async () => {
            try {
                validateConfig()

                saving.value = true
                // 只提交本页面相关的配置
                const configToSave = {
                    output_channel_group: props.config.output_channel_group,
                    deprecated_origin_channel_group: props.config.deprecated_origin_channel_group
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
            groupDrag,
            editingGroup,
            dragEnterGroup,
            groupNames,
            outputGroupOrder,
            unmappedChannels,
            handleChannelRemoved,
            addNewGroup,
            updateGroupName,
            deleteGroup,
            startEdit,
            finishEdit,
            cancelEdit,
            save
        }
    }
}
</script>