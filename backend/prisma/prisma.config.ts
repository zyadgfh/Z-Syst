import 'dotenv/config';
import type { PrismaConfig } from 'prisma';

const config: PrismaConfig = {
  migrate: {
    path: './prisma/migrations',
  },
};

export default config;