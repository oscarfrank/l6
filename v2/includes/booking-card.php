<?php
// Dashboard card for one booking (FR9). $booking comes from dashboard.php.
$bookingStatus = strtolower(trim((string) ($booking['status'] ?? '')));
$bookingLabel  = booking_status_label($booking['status'] ?? '');
?>
<article class="card trip-card">
    <div class="card-image">
        <span class="<?php echo escape(booking_status_class($booking['status'] ?? '')); ?>">
            <?php echo escape($bookingLabel); ?>
        </span>
        <img
            src="<?php echo escape(destination_image($booking['destination'] ?? '')); ?>"
            alt="<?php echo escape($booking['destination'] ?: $booking['package_name']); ?>"
        >
    </div>
    <div class="card-body">
        <h3><?php echo escape($booking['package_name']); ?></h3>
        <p class="meta"><?php echo escape($booking['destination']); ?></p>
        <p><?php echo escape(format_date($booking['travel_date'])); ?></p>
        <p class="price"><?php echo escape(format_price($booking['price'])); ?> <span class="meta">pp</span></p>
        <?php if ($bookingStatus === 'requested'): ?>
            <p class="meta">Awaiting confirmation from the London team.</p>
        <?php elseif ($bookingStatus === 'declined'): ?>
            <p class="meta">This request was not taken forward. Choose another offer or call a branch.</p>
        <?php endif; ?>
    </div>
</article>
