<template>
    <v-app-bar color="primary">
        <!-- 左侧区域 -->
        <div style="width: 56px"> <!-- 预留与右侧主题按钮相同的宽度 -->
            <v-btn v-if="showBack" variant="text" @click="$emit('back')">
                返回
            </v-btn>
        </div>

        <!-- 标题居中 -->
        <v-app-bar-title class="text-center flex-grow-1">{{ title }}</v-app-bar-title>

        <!-- 右侧主题切换按钮 -->
        <div style="width: 56px">
            <v-btn icon @click="toggleTheme" :title="isDark ? '切换到亮色模式' : '切换到暗色模式'">
                <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
            </v-btn>
        </div>
    </v-app-bar>
</template>

<script>
import { computed, onMounted, onUnmounted } from 'vue';
import { useTheme } from 'vuetify';

export default {
    name: 'AppHeader',
    props: {
        title: {
            type: String,
            required: true
        },
        showBack: {
            type: Boolean,
            default: false
        }
    },
    emits: ['back'],
    setup() {
        const theme = useTheme()
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)')

        const updateTheme = (e) => {
            const themeName = e.matches ? 'dark' : 'light'
            theme.global.name.value = themeName
            localStorage.setItem('theme', themeName)
        }

        onMounted(() => {
            const savedTheme = localStorage.getItem('theme')
            if (savedTheme) {
                theme.global.name.value = savedTheme
            } else {
                updateTheme(prefersDark)
            }
            prefersDark.addEventListener('change', updateTheme)
        })

        onUnmounted(() => {
            prefersDark.removeEventListener('change', updateTheme)
        })

        const isDark = computed(() => theme.global.current.value.dark)

        const toggleTheme = () => {
            const newTheme = isDark.value ? 'light' : 'dark'
            theme.global.name.value = newTheme
            localStorage.setItem('theme', newTheme)
        }

        return {
            isDark,
            toggleTheme
        }
    }
}
</script>