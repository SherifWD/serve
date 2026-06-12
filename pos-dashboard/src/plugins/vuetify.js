import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'restaurantSuiteDark',
    themes: {
      restaurantSuiteDark: {
        dark: true,
        colors: {
          background: '#09111d',
          surface: '#111c2b',
          primary: '#3ecf8e',
          secondary: '#1d2c3d',
          accent: '#5fb3ff',
          error: '#ef4444',
          warning: '#f59e0b',
          success: '#22c55e',
          info: '#5fb3ff',
        },
      },
      restaurantSuiteLight: {
        dark: false,
        colors: {
          background: '#f5f7fb',
          surface: '#ffffff',
          primary: '#128663',
          secondary: '#e7eef6',
          accent: '#2563eb',
          error: '#dc2626',
          warning: '#d97706',
          success: '#16a34a',
          info: '#2563eb',
        },
      },
    },
  },
})
