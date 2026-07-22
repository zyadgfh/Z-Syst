import { AuthService } from './auth.service';
import { RegisterDto } from './dto/register.dto';
import { LoginDto } from './dto/login.dto';
export declare class AuthController {
    private readonly authService;
    constructor(authService: AuthService);
    register(registerDto: RegisterDto): Promise<{
        user: {
            email: string;
            password: string;
            role: import("@prisma/client").$Enums.Role;
            id: number;
            createdAt: Date;
            updatedAt: Date;
            branchId: number | null;
        };
        token: string;
    }>;
    login(loginDto: LoginDto): Promise<{
        user: {
            email: string;
            password: string;
            role: import("@prisma/client").$Enums.Role;
            id: number;
            createdAt: Date;
            updatedAt: Date;
            branchId: number | null;
        };
        token: string;
    }>;
}
