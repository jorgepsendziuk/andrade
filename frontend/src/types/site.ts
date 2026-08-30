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

export interface FooterLink {
  id: string;
  label: string;
  href: string;
}

export interface FooterColumn {
  id: string;
  title: string;
  links: FooterLink[];
}

export interface FooterSocial {
  id: string;
  label: string;
  url: string;
  icon: string;
}

export interface SiteFooter {
  description: string;
  columns: FooterColumn[];
  social: FooterSocial[];
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
  footer?: SiteFooter;
  sections: SiteSection[];
}

export interface User {
  id: string;
  username: string;
  name: string;
}

export interface ConditionPage {
  slug: string;
  title: string;
  metaDescription: string;
  icon: string;
  whoCan: string;
  benefits: string[];
  documents: string[];
  howItWorks: string[];
  faq: Array<{ question: string; answer: string }>;
  allConditions?: string[];
}

export interface GuiaArticle {
  slug: string;
  title: string;
  metaDescription: string;
  excerpt: string;
  content: string[];
  relatedConditions?: string[];
  publishedAt: string;
}
