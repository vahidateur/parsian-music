import React from 'react';
import { features } from '../content.js';
import { InstrumentIcon, LaurelIcon, BookIcon, NoteIcon } from '../parts/icons.js';
import { SectionMark } from '../parts/ornaments.js';
import styles from './features.module.css';

const ICONS: Record<string, React.ReactNode> = {
  instrument: <InstrumentIcon size={34} />,
  radif: <BookIcon size={34} />,
  laurel: <LaurelIcon size={34} />,
  note: <NoteIcon size={34} />,
};

/** Premium feature strip — four gilt-medallion capability cards. */
export function Features() {
  return (
    <section className={styles.section} id="features">
      <div className={styles.divider}>
        <SectionMark>Mastery · مهارت‌ها</SectionMark>
      </div>
      <div className={styles.grid}>
        {features.map((f) => (
          <article key={f.key} className={styles.item}>
            <div className={styles.iconRing}>{ICONS[f.key]}</div>
            <h3 className={styles.title}>{f.title}</h3>
            <p className={styles.desc}>{f.desc}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
