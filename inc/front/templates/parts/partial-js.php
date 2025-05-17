<style>

/* show/hide relusts depending on the state of the filters */
.cs_show-only-shows-with-ticketmaster{
 .cs_tm_dates_0 {
    display: none;
 }
}
.cs_show-only-failed-shows{
	.cs_result {
		display: none;
	}
	.cs_fail {
		display: block;
	}
}

.cs_filter-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.cs_filter-button {
    position: relative;
    display: inline-block;
}

.cs_filter-button input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.cs_filter-button label {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cs_filter-button input[type="checkbox"]:checked + label {
    background: #007bff;
    color: white;
    border-color: #0056b3;
}
</style>

<div class="cs_filter-buttons">
    <div class="cs_filter-button">
        <input type="checkbox" id="showAllShows">
        <label for="showAllShows">Show all shows</label>
    </div>
    <div class="cs_filter-button">
        <input type="checkbox" id="showFailedShows">
        <label for="showFailedShows">Show only failed shows</label>
    </div>
</div>

<script>
const ShowFilters = {
    init() {
        this.resultsContainer = document.getElementById('cs_show-results');
        this.showAllBtn = document.getElementById('showAllShows');
        this.showFailedBtn = document.getElementById('showFailedShows');

        this.loadState();
        this.bindEvents();
    },

    loadState() {
        const showAll = localStorage.getItem('showAllShows') === 'true';
        const showFailed = localStorage.getItem('showFailedShows') === 'true';

        this.showAllBtn.checked = showAll;
        this.showFailedBtn.checked = showFailed;

        this.updateClasses();
    },

    bindEvents() {
        this.showAllBtn.addEventListener('change', () => {
            if(this.showAllBtn.checked) {
                this.showFailedBtn.checked = false;
                localStorage.setItem('showFailedShows', 'false');
            }
            localStorage.setItem('showAllShows', this.showAllBtn.checked);
            this.updateClasses();
        });

        this.showFailedBtn.addEventListener('change', () => {
            if(this.showFailedBtn.checked) {
                this.showAllBtn.checked = false;
                localStorage.setItem('showAllShows', 'false');
            }
            localStorage.setItem('showFailedShows', this.showFailedBtn.checked);
            this.updateClasses();
        });
    },

    updateClasses() {
        this.resultsContainer.classList.remove('cs_show-all-shows', 'cs_show-only-shows-with-ticketmaster', 'cs_show-only-failed-shows');

        if(this.showAllBtn.checked) {
            this.resultsContainer.classList.add('cs_show-all-shows');
        } else {
            this.resultsContainer.classList.add('cs_show-only-shows-with-ticketmaster');
        }

        if(this.showFailedBtn.checked) {
            this.resultsContainer.classList.add('cs_show-only-failed-shows');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => ShowFilters.init());
</script>
