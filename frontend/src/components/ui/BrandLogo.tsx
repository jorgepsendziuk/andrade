interface BrandLogoProps {
  src: string;
  alt?: string;
  className?: string;
  imgClassName?: string;
  markClassName?: string;
}

export function BrandLogo({
  src,
  alt = '',
  className = '',
  imgClassName = '',
  markClassName = 'text-brand-600',
}: BrandLogoProps) {
  return (
    <span className={`inline-flex items-start gap-0.5 ${className}`}>
      <img src={src} alt={alt} className={imgClassName} />
      <sup
        className={`text-sm md:text-base font-bold leading-none mt-1 md:mt-1.5 select-none ${markClassName}`}
        aria-hidden
      >
        ®
      </sup>
    </span>
  );
}
