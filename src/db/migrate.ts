import { config } from 'dotenv';
config();

import { db } from './index';
import { SCHEMA_SQL } from './schema';
import { seedDefaults } from './seed';

async function migrate() {
  console.log('Running database migration...');
  try {
    db().execute(SCHEMA_SQL);
    console.log('Schema created successfully.');
    await seedDefaults();
    console.log('Default data seeded.');
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
  console.log('Migration complete.');
}

migrate();
