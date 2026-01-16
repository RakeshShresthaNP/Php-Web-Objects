import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  const env = loadEnv(mode, process.cwd(), '')
  
  // Base path configuration:
  // Set VITE_BASE_PATH in .env file to configure custom directory path
  // Examples:
  //   VITE_BASE_PATH=/echo-wallet/  (for domain.com/echo-wallet/)
  //   VITE_BASE_PATH=/app/           (for domain.com/app/)
  //   VITE_BASE_PATH=/               (for root domain - default)
  // Make sure to include leading and trailing slashes
  return {
    plugins: [vue()],
    base: env.VITE_BASE_PATH || '/',
    define: {
    	'process.env': {}
    },
    resolve: {
    	alias: {
    		'@': resolve(__dirname, 'src')
    	}
    }
  }
})

