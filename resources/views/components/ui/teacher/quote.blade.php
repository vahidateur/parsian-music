@props(['quote', 'author'])

<aside class="quote-card" aria-labelledby="quote-title">
    <div class="quote-card__inner">
        <span class="quote-card__ornament" aria-hidden="true">✦</span>
        <h2 id="quote-title">نکته‌های استاد</h2>
        <blockquote>
            <span class="quote-mark" aria-hidden="true">«</span>
            <p>{{ $quote }}</p>
            <footer>{{ $author }}</footer>
            <span class="quote-mark quote-mark--end" aria-hidden="true">»</span>
        </blockquote>
    </div>
</aside>
