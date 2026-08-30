import { Star } from 'lucide-react';

export function StarRating({ rating = 5, size = 16 }: { rating?: number; size?: number }) {
  return (
    <div className="flex gap-0.5" aria-label={`${rating} de 5 estrelas`}>
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          size={size}
          className={i < rating ? 'text-gold fill-gold drop-shadow-sm' : 'text-slate-300'}
        />
      ))}
    </div>
  );
}
