<fieldset>
    <input type="hidden" class="date_filter_action" value="$action" />
    <% if use_subset %>
    <div id="subset" class="field text date inline">
        <label for="range">Select version of survey</label>
        <div class="middleColumn">
            <select id="range">
                <option selected value="OLD">V1</option>
                <option value="MARCH_2015">V2 (March 2015)</option>
                <% if $UseSurveyBuilder == "1" %>
                <option value="FALL_2015">V3 (Fall 2015)</option>
                <% end_if %>
            </select>
        </div>
    </div>
    <% end_if %>
    <div id="date-from" class="field text date inline">
        <label class="left" for="SurveyDateFilters_date-from">Start Date</label>
        <div class="middleColumn">
            <input type="text" class="text date inline date_filter_date-from" id="SurveyDateFilters_date-from" value="$start_date">
        </div>
    </div>
    <div id="date-to" class="field text date inline">
        <label class="left" for="SurveyDateFilters_date-to">End Date</label>
        <div class="middleColumn">
            <input type="text" class="text date inline date_filter_date-to" id="SurveyDateFilters_date-to" value="$end_date">
        </div>
    </div>
    <div class="Actions inline">
        <input class="action submit_filters date_filter_submit" type="button" value="Go !" title="Go !">
    </div>
    <div class="clear"><!-- --></div>
</fieldset>
