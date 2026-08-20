<?php
// Offer card for Version 1. CTA goes to the contact form rather than a booking request.
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
        <p class="card-cta"><a href="<?php echo escape(enquiry_url([
            'about'       => 'offer',
            'title'       => $offer['title'] ?? '',
            'destination' => $offer['destination'] ?? '',
            'price'       => $offer['price'] ?? '',
        ])); ?>">Ask a branch to book</a></p>
    </div>
</article>
