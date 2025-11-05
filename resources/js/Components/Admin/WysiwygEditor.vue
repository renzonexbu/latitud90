<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    variables: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3],
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Underline,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none p-4',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

// Sincronizar cambios externos
watch(() => props.modelValue, (value) => {
    const isSame = editor.value.getHTML() === value;
    if (!isSame && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value, false);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const insertVariable = (variable) => {
    if (editor.value) {
        editor.value.chain().focus().insertContent(`{{${variable}}}`).run();
    }
};

const setLink = () => {
    const url = window.prompt('URL');
    if (url) {
        editor.value.chain().focus().setLink({ href: url }).run();
    }
};

defineExpose({
    insertVariable,
});
</script>

<template>
    <div class="wysiwyg-editor border border-gray-300 rounded-lg overflow-hidden">
        <!-- Toolbar -->
        <div v-if="editor" class="bg-gray-50 border-b border-gray-300 p-2 flex flex-wrap gap-1">
            <!-- Text Formatting -->
            <div class="flex gap-1 border-r border-gray-300 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bold') }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Negrita"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('italic') }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Cursiva"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h10M8 20h10M14 4L10 20" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('underline') }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Subrayado"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v8a5 5 0 0010 0V4M5 21h14" />
                    </svg>
                </button>
            </div>

            <!-- Headings -->
            <div class="flex gap-1 border-r border-gray-300 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 1 }) }"
                    class="px-2 py-1 hover:bg-gray-200 rounded text-sm font-medium"
                    title="Título 1"
                >
                    H1
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 2 }) }"
                    class="px-2 py-1 hover:bg-gray-200 rounded text-sm font-medium"
                    title="Título 2"
                >
                    H2
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setParagraph().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('paragraph') }"
                    class="px-2 py-1 hover:bg-gray-200 rounded text-sm font-medium"
                    title="Párrafo"
                >
                    P
                </button>
            </div>

            <!-- Lists -->
            <div class="flex gap-1 border-r border-gray-300 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bulletList') }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Lista con viñetas"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('orderedList') }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Lista numerada"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h12M9 12h12M9 19h12M5 5l-1 1-1-1M5 12l-1 1-1-1M5 19l-1 1-1-1" />
                    </svg>
                </button>
            </div>

            <!-- Alignment -->
            <div class="flex gap-1 border-r border-gray-300 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('left').run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'left' }) }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Alinear izquierda"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h12M3 18h18" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('center').run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'center' }) }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Centrar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M7 12h10M3 18h18" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('right').run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'right' }) }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Alinear derecha"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M9 12h12M3 18h18" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('justify').run()"
                    :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive({ textAlign: 'justify' }) }"
                    class="p-2 hover:bg-gray-200 rounded"
                    title="Justificar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
            </div>

            <!-- Actions -->
            <div class="flex gap-1">
                <button
                    type="button"
                    @click="editor.chain().focus().undo().run()"
                    :disabled="!editor.can().undo()"
                    class="p-2 hover:bg-gray-200 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Deshacer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().redo().run()"
                    :disabled="!editor.can().redo()"
                    class="p-2 hover:bg-gray-200 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Rehacer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Editor con scroll -->
        <div class="bg-white overflow-y-auto" style="max-height: 500px;">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>

<style>
/* Estilos básicos para el editor */
.ProseMirror {
    outline: none;
    min-height: 450px;
}

.ProseMirror p {
    margin: 0.5rem 0;
}

.ProseMirror h1 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 1rem 0 0.5rem;
}

.ProseMirror h2 {
    font-size: 1.25rem;
    font-weight: bold;
    margin: 1rem 0 0.5rem;
}

.ProseMirror ul,
.ProseMirror ol {
    padding-left: 1.5rem;
    margin: 0.5rem 0;
}

.ProseMirror ul {
    list-style-type: disc;
}

.ProseMirror ol {
    list-style-type: decimal;
}

.ProseMirror li {
    margin: 0.25rem 0;
}

.ProseMirror strong {
    font-weight: bold;
}

.ProseMirror em {
    font-style: italic;
}

.ProseMirror u {
    text-decoration: underline;
}

.ProseMirror [style*="text-align: left"] {
    text-align: left;
}

.ProseMirror [style*="text-align: center"] {
    text-align: center;
}

.ProseMirror [style*="text-align: right"] {
    text-align: right;
}

.ProseMirror [style*="text-align: justify"] {
    text-align: justify;
}
</style>
