<?php
// FR12 toolbar for flights. Hidden fields keep origin/destination/date
// when the customer changes stops / price / duration. PHP applies those
// with bound placeholders. JS submits on change; Apply still works without JS.
?>
<form class="results-toolbar" method="get" action="" data-result-filters>
    <input type="hidden" name="origin" value="<?php echo escape($origin); ?>">
    <input type="hidden" name="destination" value="<?php echo escape($destination); ?>">
    <input type="hidden" name="date" value="<?php echo escape($date); ?>">
    <p class="results-toolbar-label">Refine results</p>
    <div class="results-toolbar-grid">
        <div>
            <label for="result_max_stops">Stops</label>
            <select id="result_max_stops" name="max_stops">
                <option value=""<?php echo selected_attr($maxStops, ''); ?>>Any</option>
                <option value="0"<?php echo selected_attr($maxStops, '0'); ?>>Direct only</option>
                <option value="1"<?php echo selected_attr($maxStops, '1'); ?>>1 stop max</option>
                <option value="2"<?php echo selected_attr($maxStops, '2'); ?>>2 stops max</option>
            </select>
        </div>
        <div>
            <label for="result_max_price">Price</label>
            <select id="result_max_price" name="max_price">
                <option value=""<?php echo selected_attr($maxPrice, ''); ?>>Any price</option>
                <option value="100"<?php echo selected_attr($maxPrice, '100'); ?>>Under £100</option>
                <option value="250"<?php echo selected_attr($maxPrice, '250'); ?>>Under £250</option>
                <option value="500"<?php echo selected_attr($maxPrice, '500'); ?>>Under £500</option>
            </select>
        </div>
        <div>
            <label for="result_max_duration">Travel time</label>
            <select id="result_max_duration" name="max_duration">
                <option value=""<?php echo selected_attr($maxDuration, ''); ?>>Any duration</option>
                <option value="180"<?php echo selected_attr($maxDuration, '180'); ?>>Under 3 hours</option>
                <option value="360"<?php echo selected_attr($maxDuration, '360'); ?>>Under 6 hours</option>
                <option value="720"<?php echo selected_attr($maxDuration, '720'); ?>>Under 12 hours</option>
            </select>
        </div>
        <div>
            <label for="result_sort">Sort by</label>
            <select id="result_sort" name="sort">
                <option value="price"<?php echo selected_attr($sort, 'price'); ?>>Price (low to high)</option>
                <option value="duration"<?php echo selected_attr($sort, 'duration'); ?>>Travel time (shortest)</option>
            </select>
        </div>
        <div class="search-bar-action">
            <button type="submit" class="btn btn-ghost" data-filter-submit>Apply filters</button>
        </div>
    </div>
    <p class="meta">
        <?php echo count($flights); ?> of <?php echo count($routeFlights); ?> shown.
    </p>
</form>
