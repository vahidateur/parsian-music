import React from 'react';
import { Crest, GoldDivider } from '../parts/ornaments.js';
import { PinIcon, MailIcon, PhoneIcon, NoteIcon } from '../parts/icons.js';
import styles from './footer.module.css';

const EXPLORE = ['اساتید آکادمی', 'دوره‌های موسیقی دستگاهی', 'کنسرت‌ها و اجراها', 'گالری تصاویر'];

/** Elegant footer with crest, navigation and contact details. */
export function Footer() {
  return (
    <footer className={styles.footer}>
      <div className={styles.topRule}>
        <GoldDivider width={320} />
      </div>

      <div className={styles.grid}>
        <div>
          <div className={styles.brand}>
            <Crest size={52} />
            <div className={styles.brandText}>
              <span className={styles.brandTitle}>آکادمی موسیقی پارسیان</span>
              <span className={styles.brandSub}>Musica Nobilis · Conservatoire</span>
              <p className={styles.blurb}>
                خانهٔ نغمه‌های اصیل ایران؛ جایی که سنت و هنر، سینه‌به‌سینه به نسل‌های آینده سپرده می‌شود.
              </p>
            </div>
          </div>
        </div>

        <div>
          <div className={styles.colTitle}>Explore · کاوش</div>
          {EXPLORE.map((l) => (
            <div key={l} className={styles.link}>
              <NoteIcon size={16} />
              {l}
            </div>
          ))}
        </div>

        <div>
          <div className={styles.colTitle}>Contact · ارتباط</div>
          <div className={styles.link}>
            <PinIcon size={16} />
            تالار مرکزی، خیابان هنر، تهران
          </div>
          <div className={styles.link}>
            <PhoneIcon size={16} />
            ۰۲۱ — ۲۲ ۳۳ ۴۴ ۵۵
          </div>
          <div className={styles.link}>
            <MailIcon size={16} />
            maestro@parsian-academy.ir
          </div>
        </div>
      </div>

      <div className={styles.bottom}>
        <span className={styles.copy}>© ۱۴۰۳ آکادمی موسیقی پارسیان — تمامی حقوق محفوظ است.</span>
        <span className={styles.latin}>Crafted with reverence for the art</span>
      </div>
    </footer>
  );
}
