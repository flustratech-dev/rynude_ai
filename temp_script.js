
function artifactPanelState() {
    return {
        currentArtifact: null,
        artifacts: [],
        filteredArtifacts: [],
        activeTab: 'code',
        versions: [],
        fullscreen: false,
        searchQuery: '',
        copied: false,
        downloading: false,
        loading: false,

        init: function() {
            this.loadArtifacts();
            window.addEventListener('openArtifact', function(e) {
                if (e.detail && e.detail.id) this.loadArtifact(e.detail.id);
            }.bind(this));
            window.addEventListener('closeArtifactPanel', function() {
                this.currentArtifact = null;
                this.loadArtifacts();
            }.bind(this));
        },

        loadArtifacts: function() {
            this.loading = true;
            fetch('/api/artifacts', {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    this.artifacts = resp.data || [];
                    this.filteredArtifacts = this.artifacts;
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        filterArtifacts: function() {
            var q = this.searchQuery.toLowerCase().trim();
            if (!q) { this.filteredArtifacts = this.artifacts; return; }
            this.filteredArtifacts = this.artifacts.filter(function(a) {
                return (a.title && a.title.toLowerCase().includes(q)) || (a.language && a.language.toLowerCase().includes(q));
            });
        },

        loadArtifact: function(id) {
            this.loading = true;
            fetch('/api/artifacts/' + id, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json()})
                .then(function(resp){
                    if (resp.data) {
                        this.currentArtifact = resp.data;
                        this.versions = resp.data.versions || [];
                        this.activeTab = 'preview';
                    }
                    this.loading = false;
                }.bind(this))
                .catch(function(){this.loading=false;}.bind(this));
        },

        get artifactContent() {
            return this.currentArtifact ? (this.currentArtifact.content || '') : '';
        },

        get previewContent() {
            if (!this.currentArtifact || !this.currentArtifact.content) return '';
            var lang = this.currentArtifact.language;
            if (['react','jsx','tsx'].includes(lang)) {
                return '<!DOCTYPE html><html><head><meta charset="utf-8"/><script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin>