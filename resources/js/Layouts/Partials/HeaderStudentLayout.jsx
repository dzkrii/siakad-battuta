import ApplicationLogo from '@/Components/ApplicationLogo';
import NavigationMenu from '@/Components/NavigationMenu';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Disclosure } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { IconChevronDown, IconLayoutSidebar, IconLogout2, IconX } from '@tabler/icons-react';

export default function HeaderStudentLayout({ auth, url }) {
    return (
        <>
            <Disclosure
                as="nav"
                className="border-b border-green-300 border-opacity-25 bg-gradient-to-r from-green-500 via-green-600 to-green-700 py-4 lg:border-none"
            >
                {({ open }) => (
                    <>
                        <div className="px-6 lg:px-24">
                            <div className="relative flex h-16 items-center justify-between">
                                <div className="flex items-center">
                                    <ApplicationLogo
                                        bgLogo="from-orange-500 via-orange-600 to-orange-600"
                                        colorLogo="text-white"
                                        colorText="text-white"
                                    />
                                </div>

                                <div className="flex lg:hidden">
                                    {/* mobile */}
                                    <Disclosure.Button className="relative inline-flex items-center justify-center rounded-xl p-2 text-white hover:text-white focus:outline-none">
                                        <span className="absolute -inset-0.5" />
                                        {open ? (
                                            <IconX className="block size-6" />
                                        ) : (
                                            <IconLayoutSidebar className="block size-6" />
                                        )}
                                    </Disclosure.Button>
                                </div>

                                <div className="hidden lg:ml-4 lg:block">
                                    <div className="flex items-center">
                                        <div className="hidden lg:mx-10 lg:block">
                                            <div className="flex space-x-4">
                                                <NavigationMenu
                                                    url={route('students.dashboard')}
                                                    active={url.startsWith('/students/dashboard')}
                                                    title="Dashboard"
                                                />
                                                <NavigationMenu
                                                    url={route('students.schedules.index')}
                                                    active={url.startsWith('/students/schedules')}
                                                    title="Jadwal"
                                                />
                                                <NavigationMenu
                                                    url={route('students.study-plans.index')}
                                                    active={url.startsWith('/students/study-plans')}
                                                    title="Kartu Rencana Studi"
                                                />
                                                <NavigationMenu
                                                    url={route('students.study-results.index')}
                                                    active={url.startsWith('/students/study-results')}
                                                    title="Kartu Hasil Studi"
                                                />
                                                {/* <NavigationMenu
                                                    url={route('students.fees.index')}
                                                    active={url.startsWith('/students/fees')}
                                                    title="Pembayaran"
                                                /> */}
                                            </div>
                                        </div>

                                        {/* profile dropdown */}
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="green"
                                                    size="xl"
                                                    className="data-[state=open]:bg-orange-500 data-[state=open]:text-white"
                                                >
                                                    <Avatar className="size-8 rounded-lg">
                                                        <AvatarImage src={auth.avatar} />
                                                        <AvatarFallback className="rounded-lg text-green-600">
                                                            {auth.name.substring(0, 1)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="grid flex-1 text-left text-sm leading-tight">
                                                        <span className="truncate font-semibold">{auth.name}</span>
                                                        <span className="truncate text-xs">
                                                            {auth.student.student_number} ({auth.student.classroom.name}
                                                            )
                                                        </span>
                                                    </div>
                                                    <IconChevronDown className="ml-auto size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                                                side="bottom"
                                                align="end"
                                                sideOffset={4}
                                            >
                                                <DropdownMenuLabel className="p-0 font-normal">
                                                    <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                                        <Avatar className="size-8 rounded-lg">
                                                            <AvatarImage src={auth.avatar} />
                                                            <AvatarFallback className="rounded-lg text-green-600">
                                                                {auth.name.substring(0, 1)}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div className="grid flex-1 text-left text-sm leading-tight">
                                                            <span className="truncate font-semibold">{auth.name}</span>
                                                            <span className="truncate text-xs">
                                                                {auth.student.student_number} (
                                                                {auth.student.classroom.name})
                                                            </span>
                                                        </div>
                                                    </div>
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('logout')} method="post" as="button">
                                                        <IconLogout2 />
                                                        Logout
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Disclosure.Panel className="lg:hidden">
                            <div className="space-y-1 px-2 pb-3 pt-2">
                                <Disclosure.Button
                                    as="a"
                                    href={route('students.dashboard')}
                                    className={cn(
                                        url.startsWith('/students/dashboard')
                                            ? 'bg-green-500 text-white'
                                            : 'text-white hover:bg-green-500',
                                        'block rounded-md px-3 py-2 text-base font-medium',
                                    )}
                                >
                                    Dashboard
                                </Disclosure.Button>
                                <Disclosure.Button
                                    as="a"
                                    href={route('students.schedules.index')}
                                    className={cn(
                                        url.startsWith('/students/schedules')
                                            ? 'bg-green-500 text-white'
                                            : 'text-white hover:bg-green-500',
                                        'block rounded-md px-3 py-2 text-base font-medium',
                                    )}
                                >
                                    Jadwal
                                </Disclosure.Button>
                                <Disclosure.Button
                                    as="a"
                                    href={route('students.study-plans.index')}
                                    className={cn(
                                        url.startsWith('/students/study-plans')
                                            ? 'bg-green-500 text-white'
                                            : 'text-white hover:bg-green-500',
                                        'block rounded-md px-3 py-2 text-base font-medium',
                                    )}
                                >
                                    Kartu Rencana Studi
                                </Disclosure.Button>
                                <Disclosure.Button
                                    as="a"
                                    href={route('students.study-results.index')}
                                    className={cn(
                                        url.startsWith('/students/study-results')
                                            ? 'bg-green-500 text-white'
                                            : 'text-white hover:bg-green-500',
                                        'block rounded-md px-3 py-2 text-base font-medium',
                                    )}
                                >
                                    Kartu Hasil Studi
                                </Disclosure.Button>
                                {/* <Disclosure.Button
                                    as="a"
                                    href={route('students.fees.index')}
                                    className={cn(
                                        url.startsWith('/students/fees')
                                            ? 'bg-green-500 text-white'
                                            : 'text-white hover:bg-green-500',
                                        'block rounded-md px-3 py-2 text-base font-medium',
                                    )}
                                >
                                    Pembayaran
                                </Disclosure.Button> */}
                            </div>
                            <div className="pb-3 pt-4">
                                <div className="flex items-center px-5">
                                    <div className="flex-shrink-0">
                                        <Avatar>
                                            <AvatarImage src={auth.avatar} />
                                            <AvatarFallback>{auth.name.substring(0, 1)}</AvatarFallback>
                                        </Avatar>
                                    </div>
                                    <div className="ml-3">
                                        <div className="text-base font-medium text-white">{auth.name}</div>
                                        <div className="text-sm font-medium text-white">
                                            {auth.student.student_number} ({auth.student.classroom.name})
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 space-y-1 px-2">
                                    <Disclosure.Button
                                        as={Link}
                                        href={route('logout')}
                                        method="post"
                                        className="block rounded-md px-3 py-2 text-base font-medium text-white hover:bg-green-500"
                                    >
                                        Logout
                                    </Disclosure.Button>

                                    {/* <ul className="mt-4 list-none">
                                        <li className="list-none">
                                            <NavLink
                                                url={route('logout')}
                                                method="post"
                                                as="button"
                                                active={url.startsWith('/logout')}
                                                title="Logout"
                                                icon={IconLogout2}
                                                className="list-none"
                                            />
                                        </li>
                                    </ul> */}
                                </div>
                            </div>
                        </Disclosure.Panel>
                    </>
                )}
            </Disclosure>
            <header className="py-6"></header>
        </>
    );
}
