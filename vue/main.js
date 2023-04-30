const app = Vue.createApp({
    data() {
        return {
            contents: null,
        }
    },
    methods: {
        loadPage() {
            // Simple GET request using fetch
            fetch("http://localhost:8000")
                .then(response => response.json())
                .then(({html, scripts, styles}) => {
                    this.contents = html.replace(/\\/g, '');
                    this.handleScripts(scripts);
                    this.handleStyles(styles);
                });
        },
        handleScripts(scripts) {
            //Proxy Object
            Reflect.ownKeys(scripts).forEach(scriptProp => {
                const script = scripts[scriptProp];
                const scriptEl = document.createElement('script');;
                switch (script.type) {
                    case 'inline':
                        scriptEl.innerHTML = script.content.replace(/\\/g, '');
                        this.$refs.scripts.appendChild(scriptEl);
                        break;
                    case 'src':
                        scriptEl.setAttribute('src', script.src);
                        this.$refs.scripts.appendChild(scriptEl);
                        break;                        
                    default:
                        break;
                }
            });
        },
        handleStyles(styles) {
            //Proxy Object
            Reflect.ownKeys(styles).forEach(styleProp => {
                const style = styles[styleProp];
                let styleEl;
                switch (style.type) {
                    case 'inline':
                        styleEl = document.createElement('style');
                        styleEl.innerHTML = style.content.replace(/\\/g, '');
                        this.$refs.styles.appendChild(styleEl);
                        break;
                    case 'src':
                        styleEl = document.createElement('link');
                        styleEl.setAttribute('href', style.src);
                        styleEl.setAttribute('rel', 'stylesheet');
                        this.$refs.styles.appendChild(styleEl);
                        break;                        
                    default:
                        break;
                }
            });
        }
    },
});

app.mount('#app');
