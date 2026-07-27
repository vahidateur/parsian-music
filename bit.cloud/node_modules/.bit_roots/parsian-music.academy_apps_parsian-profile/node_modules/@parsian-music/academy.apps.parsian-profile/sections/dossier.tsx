import React from 'react';
import { StoneCard, CardHeader } from '../parts/stone-card.js';
import { QuillIcon, CrownIcon } from '../parts/icons.js';
import { biography, professionalInfo, teacher } from '../content.js';
import styles from './dossier.module.css';

/**
 * Two-column dossier: an editorial biography card beside a
 * professional-information card.
 */
export function Dossier() {
  return (
    <section className={styles.section} id="biography">
      <div className={styles.grid}>
        <StoneCard>
          <CardHeader icon={<QuillIcon size={26} />} kicker="Biography" title="زندگی‌نامه" />
          <div className={styles.bioText}>
            {biography.map((p, i) => (
              <p key={i}>{p}</p>
            ))}
            <div className={styles.signature}>
              <div>
                <div className={styles.signName}>{teacher.name}</div>
                <div className={styles.signRole}>{teacher.latinName}</div>
              </div>
            </div>
          </div>
        </StoneCard>

        <StoneCard>
          <CardHeader icon={<CrownIcon size={26} />} kicker="Credentials" title="اطلاعات حرفه‌ای" />
          <div className={styles.infoList}>
            {professionalInfo.map((row) => (
              <div key={row.label} className={styles.infoRow}>
                <span className={styles.infoLabel}>{row.label}</span>
                <span className={styles.infoValue}>{row.value}</span>
              </div>
            ))}
          </div>
        </StoneCard>
      </div>
    </section>
  );
}
