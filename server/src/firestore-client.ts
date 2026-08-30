type Firestore = import('@google-cloud/firestore').Firestore;

let firestore: Firestore | null = null;

export const useFirestore =
  process.env.CONTACTS_STORAGE === 'firestore' ||
  (process.env.CONTACTS_STORAGE !== 'file' && Boolean(process.env.K_SERVICE));

export async function getFirestore(): Promise<Firestore> {
  if (!firestore) {
    const { Firestore } = await import('@google-cloud/firestore');
    firestore = new Firestore({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
      ignoreUndefinedProperties: true,
    });
  }
  return firestore;
}
