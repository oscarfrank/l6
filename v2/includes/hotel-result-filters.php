<?php
// Same idea as the flight toolbar: price and star rating for hotels (FR12).
?>
<form class="results-toolbar" method="get" action="" data-result-filters>
    <input type="hidden" name="city" value="<?php echo escape($city); ?>">
    <input type="hidden" name="check_in" value="<?php echo escape($checkIn); ?>">
    <input type="hidden" name="check_out" value="<?php echo escape($checkOut); ?>">
    <p class="results-toolbar-label">Refine results</p>
    <div class="results-toolbar-grid">
        <div>
            <label for="result_max_price">Price per night</label>
            <select id="result_max_price" name="max_price">
                <option value=""<?php echo selected_attr($maxPrice, ''); ?>>Any price</option>
                <option value="150"<?php echo selected_attr($maxPrice, '150'); ?>>Under £150</option>
                <option value="250"<?php echo selected_attr($maxPrice, '250'); ?>>Under £250</option>
                <option value="400"<?php echo selected_attr($maxPrice, '400'); ?>>Under £400</option>
            </select>
        </div>
        <div>
            <label for="result_min_stars">Star rating</label>
            <select id="result_min_stars" name="min_stars">
                <option value=""<?php echo selected_attr($minStars, ''); ?>>Any stars</option>
                <option value="3"<?php echo selected_attr($minStars, '3'); ?>>3 stars and above</option>
                <option value="4"<?php echo selected_attr($minStars, '4'); ?>>4 stars and above</option>
                <option value="5"<?php echo selected_attr($minStars, '5'); ?>>5 stars only</option>
            </select>
        </div>
        <div>
            <label for="result_sort">Sort by</label>
            <select id="result_sort" name="sort">
                <option value="price"<?php echo selected_attr($sort, 'price'); ?>>Price (low to high)</option>
                <option value="rating"<?php echo selected_attr($sort, 'rating'); ?>>Star rating (high to low)</option>
            </select>
        </div>
        <div class="search-bar-action">
            <button type="submit" class="btn btn-ghost" data-filter-submit>Apply filters</button>
        </div>
    </div>
    <p class="meta">
        <?php echo count($hotels); ?> of <?php echo count($cityHotels); ?> shown.
    </p>
</form>
