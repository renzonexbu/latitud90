<script setup>
  import InputError from "@/Components/InputError.vue";
  import InputLabel from "@/Components/InputLabel.vue";
  import PrimaryButton from "@/Components/PrimaryButton.vue";
  import TextInput from "@/Components/TextInput.vue";
  import SvgIcon from "@/Components/Icons/SvgIcon.vue";
  import { Link, useForm, usePage } from "@inertiajs/vue3";

  defineProps({
    mustVerifyEmail: {
      type: Boolean
    },
    status: {
      type: String
    }
  });

  const user = usePage().props.auth.user;

  const form = useForm({
    name: user.name,
    email: user.email
  });

  const toProperCase = (str) => {
      if (!str) return '';
      return str.trim().split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
  };

  const submitProfile = () => {
      form.name = toProperCase(form.name);
      form.patch(route('profile.update'));
  };
</script>

<template>
  <section>
    <header>
      <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>

      <p class="mt-1 text-sm text-gray-600">
        Update your account's profile information and email address.
      </p>
    </header>

    <form
      @submit.prevent="submitProfile"
      class="mt-6 space-y-6">
      <div>
        <InputLabel
          for="name"
          value="Name" />
        <div class="relative">
          <div
            class="absolute inset-y-0 end-0 top-0 flex items-center ps-3.5 pointer-events-none">
            <SvgIcon
              name="edit"
              :size="16"
              class="mr-2"
              color-type="stroke" />
          </div>
          <TextInput
            id="name"
            type="text"
            class="mt-1 block w-full"
            v-model="form.name"
            required
            autofocus
            autocomplete="name" />
        </div>
        <InputError
          class="mt-2"
          :message="form.errors.name" />
      </div>

      <div>
        <InputLabel
          for="email"
          value="Email" />
        <div class="relative">
          <div
            class="absolute inset-y-0 end-0 top-0 flex items-center ps-3.5 pointer-events-none">
            <SvgIcon
              name="edit"
              :size="16"
              class="mr-2"
              color-type="stroke" />
          </div>
          <TextInput
            id="email"
            type="email"
            class="mt-1 block w-full"
            v-model="form.email"
            required
            autocomplete="username" />
        </div>
        <InputError
          class="mt-2"
          :message="form.errors.email" />
      </div>

      <div v-if="mustVerifyEmail && user.email_verified_at === null">
        <p class="text-sm mt-2 text-gray-800">
          Your email address is unverified.
          <Link
            :href="route('verification.send')"
            method="post"
            as="button"
            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Click here to re-send the verification email.
          </Link>
        </p>

        <div
          v-show="status === 'verification-link-sent'"
          class="mt-2 font-medium text-sm text-green-600">
          A new verification link has been sent to your email address.
        </div>
      </div>

      <div class="flex items-center gap-4">
        <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>

        <Transition
          enter-active-class="transition ease-in-out"
          enter-from-class="opacity-0"
          leave-active-class="transition ease-in-out"
          leave-to-class="opacity-0">
          <p
            v-if="form.recentlySuccessful"
            class="text-sm text-gray-600">
            Guardado
          </p>
        </Transition>
      </div>
    </form>
  </section>
</template>
