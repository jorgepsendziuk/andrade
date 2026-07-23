export interface NavItem {
  id: string;
  label: string;
  href?: string;
  type: 'anchor' | 'external' | 'internal' | 'dropdown';
  highlight?: boolean;
  children?: NavItem[];
}

export interface SiteInfo {
  title: string;
  description: string;
  logo: string;
  logoFull?: string;
  phone: string;
  whatsapp: string;
  email: string;
  hours: string;
  address: string;
  googleReviewsUrl: string;
  copyright: string;
}

export interface HeroSlide {
  id: string;
  title: string;
  subtitle: string;
  videoId?: string;
  backgroundImage?: string;
  ctaText?: string;
  ctaLink?: string;
}

export interface AboutCard {
  id: string;
  heading: string;
  text: string;
}

export interface ServiceItem {
  id: string;
  title: string;
  icon: string;
}

export interface SocialLink {
  id: string;
  label: string;
  url: string;
  icon: string;
}

export interface SectionData {
  [key: string]: unknown;
}

export interface SiteSection {
  id: string;
  type: string;
  enabled: boolean;
  order: number;
  data: SectionData;
}

export interface SiteContent {
  site: SiteInfo;
  navigation: NavItem[];
  sections: SiteSection[];
}

export interface User {
  id: string;
  username: string;
  name: string;
}
