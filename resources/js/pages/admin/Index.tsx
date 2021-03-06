import React, { useMemo } from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps } from '@/api/types/default'
import Main from '@/components/Main'

interface Props extends DefaultProps {
}

export default function Index({ auth }: Props) {

  return (
    <Authenticated
      auth={auth}
      header={
        <h1 className='h1'>
          Dashboard
        </h1>
      }
    >
      <Head title='Admin Dashboard' />

      <Main>
        <h2 className='h3-deemphasized'>Admin</h2>
      </Main>
    </Authenticated>
  )
}
