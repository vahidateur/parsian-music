import React, { useEffect, useState } from 'react';
import { Crest } from './ornaments.js';
import styles from './navbar.module.css';

const NAV_LINKS = ['خانه', 'اساتید', 'دوره‌ها', 'گالری', 'ارتباط'];

/**
 * Transparent premium navbar that gains a smoked-glass backdrop
 * and gilt border once the page is scrolled.
 */
export function Navbar() {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <nav className={`${styles.nav} ${scrolled ? styles.scrolled : ''}`}>
      <div className={styles.brand}>
        <Crest size={40} />
        <div className={styles.brandText}>
          <span className={styles.brandTitle}>آکادمی پارسیان</span>
          <span className={styles.brandSub}>Musica Nobilis · Est. ۱۳۶۴</span>
        </div>
      </div>

      <div className={styles.links}>
        {NAV_LINKS.map((l) => (
          <a key={l} className={styles.link}>
            {l}
          </a>
        ))}
      </div>

      <button className={styles.cta} type="button">
        رزرو جلسه خصوصی
      </button>

      <button className={styles.burger} type="button" aria-label="منو">
        <span />
        <span />
        <span />
      </button>
    </nav>
  );
}
