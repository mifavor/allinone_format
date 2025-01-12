import axios from 'axios'

const api = axios.create({
    baseURL: '/api'
})

export const getConfig = async () => {
    try {
        const response = await api.get('/config')
        return response.data
    } catch (error) {
        throw new Error(error.response?.data?.error || '获取配置失败')
    }
}

export const updateConfig = async (config) => {
    try {
        const response = await api.post('/config', config)
        return response.data
    } catch (error) {
        throw new Error(error.response?.data?.error || '更新配置失败')
    }
}

export const getChannelUrls = async () => {
    try {
        const response = await api.get('/channel-urls')
        return response.data
    } catch (error) {
        throw new Error(error.response?.data?.error || '获取频道链接失败')
    }
}