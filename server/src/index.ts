import app from './app.js';
import { warmSitemapCache } from './sitemap.js';

const PORT = process.env.PORT || 3002;

void warmSitemapCache();

app.listen(PORT, () => {
  console.log(`API rodando em http://localhost:${PORT}`);
  void warmSitemapCache();
});
