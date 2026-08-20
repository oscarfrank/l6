<?php
// One flight row (FR10). Also reused when we suggest other dates for the same route.
?>
<article
    class="card js-result"
    data-price="<?php echo escape($flight['price']); ?>"
    data-duration="<?php echo escape($flight['duration_minutes']); ?>"
    data-stops="<?php echo (int) $flight['stops']; ?>"
>
    <div class="card-body result-row">
        <h3><?php echo escape($flight['airline']); ?></h3>
        <p class="meta">
            <?php echo escape($flight['origin']); ?>
            →
            <?php echo escape($flight['destination']); ?>
        </p>
        <p>
            Departs <?php echo escape(format_datetime($flight['depart_time'])); ?>
            · Arrives <?php echo escape(format_datetime($flight['arrive_time'])); ?>
        </p>
        <p class="meta">
            Duration <?php echo escape(format_duration($flight['duration_minutes'])); ?>
            ·
            <?php echo (int) $flight['stops'] === 0 ? 'Direct' : (int) $flight['stops'] . ' stop' . ((int) $flight['stops'] === 1 ? '' : 's'); ?>
        </p>
        <p class="price"><?php echo escape(format_price($flight['price'])); ?></p>
        <p class="card-cta"><a href="<?php echo escape(enquiry_url([
            'about'       => 'flight',
            'airline'     => $flight['airline'] ?? '',
            'origin'      => $flight['origin'] ?? '',
            'destination' => $flight['destination'] ?? '',
            'depart'      => isset($flight['depart_time']) ? format_datetime($flight['depart_time']) : '',
            'price'       => $flight['price'] ?? '',
        ])); ?>">Ask a branch to hold this fare</a></p>
    </div>
</article>
