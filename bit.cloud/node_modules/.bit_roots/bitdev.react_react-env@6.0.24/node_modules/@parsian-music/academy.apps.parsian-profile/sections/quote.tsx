import React from 'react';
import { quote } from '../content.js';
import styles from './quote.module.css';

/** A wax seal rendered as SVG for the parchment scroll. */
function WaxSeal() {
  return (
    <svg width="66" height="66" viewBox="0 0 66 66" fill="none" aria-hidden="true">
      <circle cx="33" cy="33" r="26" fill="#7a1f16" />
      <circle cx="33" cy="33" r="26" fill="url(#wax-sheen)" fillOpacity="0.5" />
      <circle cx="33" cy="33" r="21" stroke="#4a120c" strokeWidth="1.4" fill="none" />
      <path d="M27 22 V44 M27 44 C27 48 23 49 20 47 C18 46 19 42 23 42 C25 42 27 42 27 44 Z M39 20 V42 M39 42 C39 46 35 47 32 45 C30 44 31 40 35 40 C37 40 39 40 39 42 Z M27 22 L39 20"
        stroke="#f0c98a" strokeWidth="1.6" fill="none" strokeLinecap="round" />
      <defs>
        <radialGradient id="wax-sheen" cx="0.35" cy="0.3" r="0.8">
          <stop stopColor="#c0433a" />
          <stop offset="1" stopColor="#5a140d" />
        </radialGradient>
      </defs>
    </svg>
  );
}

/**
 * Parchment quote card — an aged manuscript scroll bearing the
 * maestro's words, sealed with wax.
 */
export function QuoteCard() {
  return (
    <section className={styles.section} id="quote">
      <figure className={styles.scroll}>
        <span className={styles.mark}>&ldquo;</span>
        <blockquote className={styles.text}>{quote.text}</blockquote>
        <div className={styles.rule} />
        <figcaption className={styles.author}>— {quote.author}</figcaption>
        <div className={styles.wax}>
          <WaxSeal />
        </div>
      </figure>
    </section>
  );
}
