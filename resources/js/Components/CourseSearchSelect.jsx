import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem } from '@/Components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useState } from 'react';

export default function CourseSearchSelect({ options, value, onChange, placeholder = 'Pilih mata kuliah', error }) {
    const [open, setOpen] = useState(false);

    const selectedOption = options.find((option) => option.value === value);
    const displayValue = selectedOption
        ? `${selectedOption.label} - ${selectedOption.teacher} - ${selectedOption.department}`
        : placeholder;

    return (
        <div className="w-full">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-full justify-between font-normal"
                    >
                        <span className="truncate">{displayValue}</span>
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-full p-0" align="start">
                    <Command className="w-full">
                        <div className="flex items-center border-b px-3">
                            <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                            <CommandInput placeholder="Cari mata kuliah..." className="h-9 flex-1" />
                        </div>
                        <CommandEmpty>Tidak ada mata kuliah yang ditemukan.</CommandEmpty>
                        <CommandGroup className="max-h-64 overflow-auto">
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={`${option.label} ${option.teacher} ${option.department}`}
                                    onSelect={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                    className="cursor-pointer"
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            value === option.value ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    <span className="truncate">
                                        {option.label} - {option.teacher} - {option.department}
                                    </span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </Command>
                </PopoverContent>
            </Popover>
            {error && <InputError message={error} />}
        </div>
    );
}
