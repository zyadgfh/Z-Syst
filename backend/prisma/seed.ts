import { PrismaClient, Role } from '@prisma/client';
import * as bcrypt from 'bcrypt';

const prisma = new PrismaClient();

async function main() {
  // Create default branch
  const branch = await prisma.branch.upsert({
    where: { id: 1 },
    update: {},
    create: {
      id: 1,
      name: 'Main Pharmacy',
      address: '123 Main St, City',
    },
  });

  // Create default admin user
  const adminPassword = await bcrypt.hash('123456', 10);
  const admin = await prisma.user.upsert({
    where: { email: 'admin@pharmacy.local' },
    update: {},
    create: {
      email: 'admin@pharmacy.local',
      password: adminPassword,
      role: Role.ADMIN,
      branchId: 1,
    },
  });

  // Create demo pharmacist
  const pharmPassword = await bcrypt.hash('123456', 10);
  await prisma.user.upsert({
    where: { email: 'pharmacist@pharmacy.local' },
    update: {},
    create: {
      email: 'pharmacist@pharmacy.local',
      password: pharmPassword,
      role: Role.PHARMACIST,
      branchId: 1,
    },
  });

  // Create demo products
  const products = [
    { sku: 'MED-001', name: 'Paracetamol', description: 'Pain relief tablets', price: 5.99 },
    { sku: 'MED-002', name: 'Amoxicillin', description: 'Antibiotic capsules', price: 12.5 },
    { sku: 'MED-003', name: 'Ibuprofen', description: 'Anti-inflammatory tablets', price: 8.25 },
    { sku: 'MED-004', name: 'Aspirin', description: 'Blood thinner tablets', price: 4.5 },
    { sku: 'MED-005', name: 'Vitamin C', description: 'Immune support tablets', price: 15.0 },
  ];

  for (const product of products) {
    await prisma.product.upsert({
      where: { sku: product.sku },
      update: {},
      create: product,
    });
  }

  // Create stock for each product at the main branch
  for (let i = 1; i <= products.length; i++) {
    await prisma.stock.upsert({
      where: { id: i },
      update: {},
      create: {
        productId: i,
        branchId: 1,
        quantity: Math.floor(Math.random() * 100) + 10,
        batchNumber: `BATCH-${1000 + i}`,
        expiryDate: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000), // 1 year from now
      },
    });
  }

  // Create demo patient
  const patient = await prisma.patient.upsert({
    where: { id: 1 },
    update: {},
    create: {
      name: 'John Doe',
      phoneNumber: '+1234567890',
      email: 'john@example.com',
      allergies: 'None known',
    },
  });

  console.log('Database seeded successfully!');
  console.log({ branch, admin, patient });
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });