import React from 'react';
import { CornerFlourish } from './ornaments.js';
import styles from './stone-card.module.css';

/**
 * A carved dark-stone card with layered antique-gold trim and
 * corner filigree. The signature surface used across the page.
 */
export function StoneCard({
  children,
  className,
  style,
}: {
  children: React.ReactNode;
  className?: string;
  style?: React.CSSProperties;
}) {
  return (
    <div className={`${styles.card} ${className ?? ''}`} style={style}>
      <div className={styles.trim} />
      <CornerFlourish size={54} className={`${styles.corner} ${styles.tl}`} />
      <CornerFlourish size={54} className={`${styles.corner} ${styles.tr}`} />
      <CornerFlourish size={54} className={`${styles.corner} ${styles.bl}`} />
      <CornerFlourish size={54} className={`${styles.corner} ${styles.br}`} />
      <div className={styles.inner}>{children}</div>
    </div>
  );
}

/**
 * Ornamented card header: a gilt icon medallion beside a Latin
 * kicker and Persian display title.
 */
export function CardHeader({
  icon,
  kicker,
  title,
}: {
  icon: React.ReactNode;
  kicker: string;
  title: string;
}) {
  return (
    <div className={styles.head}>
      <div className={styles.medallion}>{icon}</div>
      <div className={styles.titleGroup}>
        <span className={styles.kicker}>{kicker}</span>
        <span className={styles.title}>{title}</span>
      </div>
    </div>
  );
}
