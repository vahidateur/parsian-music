import React from 'react';
import { StoneCard, CardHeader } from '../parts/stone-card.js';
import { ClockIcon } from '../parts/icons.js';
import { SectionMark } from '../parts/ornaments.js';
import { schedule } from '../content.js';
import styles from './schedule.module.css';

/** Weekly teaching schedule rendered inside a carved-stone card. */
export function Schedule() {
  return (
    <section className={styles.section} id="schedule">
      <div className={styles.divider}>
        <SectionMark>Calendarium · برنامهٔ هفتگی</SectionMark>
      </div>
      <div className={styles.shell}>
        <StoneCard>
          <CardHeader icon={<ClockIcon size={26} />} kicker="Weekly Sessions" title="جلسات هفتگی استاد" />
          <div className={styles.list}>
            {schedule.map((s) => (
              <div key={s.day} className={styles.row}>
                <span className={styles.day}>{s.day}</span>
                <span className={styles.topic}>{s.topic}</span>
                <span className={styles.time}>{s.time}</span>
                <span className={`${styles.status} ${s.open ? styles.open : styles.full}`}>
                  {s.open ? 'ظرفیت آزاد' : 'تکمیل'}
                </span>
              </div>
            ))}
          </div>
        </StoneCard>
      </div>
    </section>
  );
}
