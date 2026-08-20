<?php
// Hotel result card (FR11). Enquiry link carries the search dates if they were filled in.
?>
<article
    class="card js-result"
    data-price="<?php echo escape($hotel['price_per_night']); ?>"
    data-rating="<?php echo escape($hotel['star_rating']); ?>"
>
    <div class="card-image">
        <img
            src="<?php echo escape(media_url($hotel['image_url'] ?? '')); ?>"
            alt="<?php echo escape($hotel['name'] . ' in ' . $hotel['city']); ?>"
        >
    </div>
    <div class="card-body">
        <h3><?php echo escape($hotel['name']); ?></h3>
        <p class="meta">
            <?php echo escape($hotel['city']); ?>
            ·
            <?php echo (int) $hotel['star_rating']; ?> star<?php echo (int) $hotel['star_rating'] === 1 ? '' : 's'; ?>
        </p>
        <p><?php echo escape($hotel['amenities']); ?></p>
        <p class="price"><?php echo escape(format_price($hotel['price_per_night'])); ?> <span class="meta">per night</span></p>
        <p class="card-cta"><a href="<?php echo escape(enquiry_url([
            'about'     => 'hotel',
            'name'      => $hotel['name'] ?? '',
            'city'      => $hotel['city'] ?? '',
            'check_in'  => !empty($checkIn) ? format_date($checkIn) : '',
            'check_out' => !empty($checkOut) ? format_date($checkOut) : '',
            'price'     => $hotel['price_per_night'] ?? '',
        ])); ?>">Ask a branch to check availability</a></p>
    </div>
</article>
