<?php
// One offer card. $offer is a row from the offers table (index.php / offers.php).
// Bestseller badge is FR1. Request link needs an account (FR9).
?>
<article class="card">
    <div class="card-image">
        <?php if (!empty($offer['is_bestseller'])): ?>
            <span class="badge">Bestseller</span>
        <?php endif; ?>
        <img
            src="<?php echo escape(media_url($offer['image_url'] ?? '')); ?>"
            alt="<?php echo escape($offer['destination'] ?: $offer['title']); ?>"
        >
    </div>
    <div class="card-body">
        <h3><?php echo escape($offer['title']); ?></h3>
        <p class="meta"><?php echo escape($offer['destination']); ?></p>
        <?php if (!empty($offer['description'])): ?>
            <p class="card-excerpt"><?php echo escape($offer['description']); ?></p>
        <?php endif; ?>
        <p class="price"><?php echo escape(format_price($offer['price'])); ?> <span class="meta">pp</span></p>
        <p class="card-cta"><a href="<?php echo escape(query_url('request-booking.php', [
            'offer_id' => (int) ($offer['id'] ?? 0),
        ])); ?>">Request this package</a></p>
    </div>
</article>
