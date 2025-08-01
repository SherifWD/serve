// src/plugins/vuetify.js
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'yellowBlack',
    themes: {
      yellowBlack: {
        dark: false,
        colors: {
          background: '#181818',
          surface: '#222',
          primary: '#2a9d8f',    // Softer yellow
          'primary-darken-1': '#FFD24C',
          secondary: '#222',
          accent: '#FFEB99',
          text: '#fff',
          card: '#232323'
        }
      }
    }
  }
})
