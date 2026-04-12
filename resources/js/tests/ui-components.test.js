import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AppButton from '../components/ui/AppButton.vue';
import AppTable from '../components/ui/AppTable.vue';

describe('shared ui components', () => {
    it('renders loading state in AppButton', () => {
        const wrapper = mount(AppButton, {
            props: {
                loading: true,
                label: 'Submit',
            },
        });

        expect(wrapper.text()).toContain('Submit');
        expect(wrapper.find('span.animate-spin').exists()).toBe(true);
    });

    it('renders rows in AppTable and empty state correctly', async () => {
        const columns = [
            { key: 'name', label: 'Name' },
            { key: 'amount', label: 'Amount' },
        ];

        const wrapper = mount(AppTable, {
            props: {
                columns,
                rows: [{ id: 1, name: 'Revenue', amount: 1000 }],
            },
        });

        expect(wrapper.text()).toContain('Revenue');
        expect(wrapper.text()).toContain('1000');

        await wrapper.setProps({ rows: [] });
        expect(wrapper.text()).toContain('No records found.');
    });
});
