import { Test, TestingModule } from '@nestjs/testing';
import { ProductsService } from './products.service';
import { PrismaService } from '../prisma/prisma.service';

describe('ProductsService', () => {
  let service: ProductsService;

  const mockPrisma = {
    product: {
      findMany: jest.fn(),
    },
    stock: {
      findMany: jest.fn(),
    },
  };

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [ProductsService, { provide: PrismaService, useValue: mockPrisma }],
    }).compile();

    service = module.get<ProductsService>(ProductsService);
    jest.clearAllMocks();
  });

  it('returns products merged with stock for the selected branch', async () => {
    mockPrisma.product.findMany.mockResolvedValue([
      { id: 1, sku: 'SKU-1', name: 'Paracetamol', description: 'Pain relief', price: 12.5 },
    ]);
    mockPrisma.stock.findMany.mockResolvedValue([{ productId: 1, quantity: 25 }]);

    const result = await service.getProducts(1);

    expect(result).toHaveLength(1);
    expect(result[0]).toEqual(
      expect.objectContaining({ id: 1, name: 'Paracetamol', stock: 25 }),
    );
  });
});
